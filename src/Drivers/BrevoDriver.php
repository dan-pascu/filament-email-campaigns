<?php

namespace DanPascu\FilamentEmailCampaigns\Drivers;

use DanPascu\FilamentEmailCampaigns\Contracts\EspDriver;
use DanPascu\FilamentEmailCampaigns\Enums\CampaignEventType;
use DanPascu\FilamentEmailCampaigns\Models\EmailProvider;
use DanPascu\FilamentEmailCampaigns\Support\EspMessage;
use DanPascu\FilamentEmailCampaigns\Support\EspSendResult;
use DanPascu\FilamentEmailCampaigns\Support\EspWebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Brevo (formerly Sendinblue) has no webhook signature either, so its
 * webhook URL carries a shared token as a query string parameter
 * (?token=...), compared against EmailProvider::webhook_signing_secret.
 */
class BrevoDriver implements EspDriver
{
    public function __construct(protected EmailProvider $provider) {}

    public static function credentialFields(): array
    {
        return [
            'api_key' => 'API key (v3)',
        ];
    }

    public function send(EspMessage $message): EspSendResult
    {
        $payload = [
            'sender' => array_filter(['email' => $message->fromEmail, 'name' => $message->fromName]),
            'to' => [array_filter(['email' => $message->toEmail, 'name' => $message->toName])],
            'subject' => $message->subject,
            'htmlContent' => $message->html,
        ];

        if ($message->replyToEmail) {
            $payload['replyTo'] = ['email' => $message->replyToEmail];
        }

        $tags = $message->tags;

        if (isset($message->metadata['send_id'])) {
            $tags[] = 'send:'.$message->metadata['send_id'];
        }

        if ($tags !== []) {
            $payload['tags'] = $tags;
        }

        $response = Http::withHeaders([
            'api-key' => (string) $this->provider->credential('api_key'),
            'Accept' => 'application/json',
        ])->post('https://api.brevo.com/v3/smtp/email', $payload);

        if ($response->failed()) {
            return EspSendResult::failure((string) ($response->json('message') ?? $response->body()));
        }

        return EspSendResult::success((string) $response->json('messageId'));
    }

    public function verifyWebhookRequest(Request $request): bool
    {
        $secret = $this->provider->webhook_signing_secret;

        if (! $secret) {
            return false;
        }

        return hash_equals($secret, (string) $request->query('token'));
    }

    public function parseWebhookRequest(Request $request): array
    {
        $payload = $request->input();
        $eventType = $this->mapEventType($payload['event'] ?? null);

        if ($eventType === null) {
            return [];
        }

        $occurredAt = isset($payload['ts'])
            ? Carbon::createFromTimestamp((int) $payload['ts'])
            : Carbon::parse($payload['date'] ?? now());

        return [
            new EspWebhookEvent(
                type: $eventType,
                messageId: $payload['message-id'] ?? null,
                occurredAt: $occurredAt,
                meta: ['send_id' => $this->extractSendId($payload)],
                clickedUrl: $payload['link'] ?? null,
            ),
        ];
    }

    protected function mapEventType(?string $event): ?CampaignEventType
    {
        return match ($event) {
            'delivered' => CampaignEventType::Delivered,
            'opened', 'unique_opened' => CampaignEventType::Opened,
            'click' => CampaignEventType::Clicked,
            'hard_bounce', 'soft_bounce', 'blocked' => CampaignEventType::Bounced,
            'spam' => CampaignEventType::Complained,
            'unsubscribed' => CampaignEventType::Unsubscribed,
            'error', 'invalid_email' => CampaignEventType::Failed,
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function extractSendId(array $payload): ?string
    {
        $tags = $payload['tags'] ?? ($payload['tag'] ?? null);
        $tags = is_array($tags) ? $tags : [$tags];

        foreach ($tags as $tag) {
            if (is_string($tag) && str_starts_with($tag, 'send:')) {
                return substr($tag, strlen('send:'));
            }
        }

        return null;
    }
}
