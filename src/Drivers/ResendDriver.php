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
 * Resend delivers webhooks through Svix, signed with HMAC-SHA256 over
 * "{svix-id}.{svix-timestamp}.{body}" using the webhook signing secret
 * Resend gives you (a "whsec_..." string), stored on
 * EmailProvider::webhook_signing_secret.
 */
class ResendDriver implements EspDriver
{
    public function __construct(protected EmailProvider $provider) {}

    public static function credentialFields(): array
    {
        return [
            'api_key' => 'API key',
        ];
    }

    public function send(EspMessage $message): EspSendResult
    {
        $payload = [
            'from' => $this->formatAddress($message->fromEmail, $message->fromName),
            'to' => [$this->formatAddress($message->toEmail, $message->toName)],
            'subject' => $message->subject,
            'html' => $message->html,
        ];

        if ($message->replyToEmail) {
            $payload['reply_to'] = $message->replyToEmail;
        }

        $tags = [];

        foreach ($message->tags as $index => $tag) {
            $tags[] = ['name' => 'tag_'.$index, 'value' => $this->sanitizeTagValue($tag)];
        }

        foreach ($message->metadata as $key => $value) {
            $tags[] = ['name' => $this->sanitizeTagValue((string) $key), 'value' => $this->sanitizeTagValue((string) $value)];
        }

        if ($tags !== []) {
            $payload['tags'] = $tags;
        }

        $response = Http::withToken((string) $this->provider->credential('api_key'))
            ->post('https://api.resend.com/emails', $payload);

        if ($response->failed()) {
            return EspSendResult::failure((string) ($response->json('message') ?? $response->body()));
        }

        return EspSendResult::success((string) $response->json('id'));
    }

    public function verifyWebhookRequest(Request $request): bool
    {
        $secret = $this->provider->webhook_signing_secret;

        if (! $secret) {
            return false;
        }

        $id = $request->header('svix-id');
        $timestamp = $request->header('svix-timestamp');
        $signatureHeader = $request->header('svix-signature');

        if (! $id || ! $timestamp || ! $signatureHeader) {
            return false;
        }

        $secretBytes = base64_decode(str_starts_with($secret, 'whsec_') ? substr($secret, 6) : $secret, strict: true);

        if ($secretBytes === false) {
            return false;
        }

        $expected = base64_encode(hash_hmac('sha256', "{$id}.{$timestamp}.{$request->getContent()}", $secretBytes, true));

        foreach (explode(' ', $signatureHeader) as $part) {
            [, $signature] = array_pad(explode(',', $part, 2), 2, '');

            if ($signature !== '' && hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }

    public function parseWebhookRequest(Request $request): array
    {
        $payload = json_decode($request->getContent(), true);

        if (! is_array($payload)) {
            return [];
        }

        $eventType = $this->mapEventType($payload['type'] ?? null);

        if ($eventType === null) {
            return [];
        }

        $data = $payload['data'] ?? [];

        return [
            new EspWebhookEvent(
                type: $eventType,
                messageId: $data['email_id'] ?? null,
                occurredAt: isset($payload['created_at']) ? Carbon::parse($payload['created_at']) : Carbon::now(),
                meta: ['send_id' => $this->extractSendId($data)],
                clickedUrl: $data['click']['link'] ?? null,
            ),
        ];
    }

    protected function mapEventType(?string $type): ?CampaignEventType
    {
        return match ($type) {
            'email.sent' => CampaignEventType::Sent,
            'email.delivered' => CampaignEventType::Delivered,
            'email.opened' => CampaignEventType::Opened,
            'email.clicked' => CampaignEventType::Clicked,
            'email.bounced' => CampaignEventType::Bounced,
            'email.complained' => CampaignEventType::Complained,
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function extractSendId(array $data): ?string
    {
        foreach ($data['tags'] ?? [] as $tag) {
            if (($tag['name'] ?? null) === 'send_id') {
                return (string) $tag['value'];
            }
        }

        return null;
    }

    protected function sanitizeTagValue(string $value): string
    {
        return substr((string) preg_replace('/[^a-zA-Z0-9_-]/', '_', $value), 0, 256);
    }

    protected function formatAddress(string $email, ?string $name): string
    {
        return $name ? "{$name} <{$email}>" : $email;
    }
}
