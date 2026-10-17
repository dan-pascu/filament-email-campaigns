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
 * Postmark has no built-in webhook signature, so delivery/bounce/open/
 * click/spam/unsubscribe webhooks are verified via HTTP Basic Auth
 * credentials embedded in the webhook URL configured in Postmark's
 * dashboard (their own recommended approach), stored as
 * "user:password" in EmailProvider::webhook_signing_secret.
 */
class PostmarkDriver implements EspDriver
{
    public function __construct(protected EmailProvider $provider) {}

    public static function credentialFields(): array
    {
        return [
            'server_token' => 'Server API token',
        ];
    }

    public function send(EspMessage $message): EspSendResult
    {
        $payload = [
            'From' => $this->formatAddress($message->fromEmail, $message->fromName),
            'To' => $this->formatAddress($message->toEmail, $message->toName),
            'Subject' => $message->subject,
            'HtmlBody' => $message->html,
        ];

        if ($message->replyToEmail) {
            $payload['ReplyTo'] = $message->replyToEmail;
        }

        if ($message->tags !== []) {
            $payload['Tag'] = $message->tags[0];
        }

        if ($message->metadata !== []) {
            $payload['Metadata'] = array_map('strval', $message->metadata);
        }

        $response = Http::withHeaders([
            'X-Postmark-Server-Token' => (string) $this->provider->credential('server_token'),
            'Accept' => 'application/json',
        ])->post('https://api.postmarkapp.com/email', $payload);

        $body = $response->json() ?? [];

        if ($response->failed() || (int) ($body['ErrorCode'] ?? 0) !== 0) {
            return EspSendResult::failure((string) ($body['Message'] ?? $response->body()));
        }

        return EspSendResult::success((string) ($body['MessageID'] ?? ''));
    }

    public function verifyWebhookRequest(Request $request): bool
    {
        $secret = $this->provider->webhook_signing_secret;

        if (! $secret) {
            return false;
        }

        $provided = $request->getUser().':'.$request->getPassword();

        return hash_equals($secret, $provided);
    }

    public function parseWebhookRequest(Request $request): array
    {
        $payload = $request->input();
        $recordType = $payload['RecordType'] ?? null;
        $eventType = $this->mapEventType($recordType, $payload);

        if ($eventType === null) {
            return [];
        }

        $occurredAt = $payload['DeliveredAt']
            ?? $payload['BouncedAt']
            ?? $payload['ReceivedAt']
            ?? null;

        return [
            new EspWebhookEvent(
                type: $eventType,
                messageId: $payload['MessageID'] ?? null,
                occurredAt: $occurredAt ? Carbon::parse($occurredAt) : Carbon::now(),
                meta: ['send_id' => $payload['Metadata']['send_id'] ?? null],
                clickedUrl: $payload['OriginalLink'] ?? null,
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function mapEventType(?string $recordType, array $payload): ?CampaignEventType
    {
        return match ($recordType) {
            'Delivery' => CampaignEventType::Delivered,
            'Open' => CampaignEventType::Opened,
            'Click' => CampaignEventType::Clicked,
            'SpamComplaint' => CampaignEventType::Complained,
            'Bounce' => ($payload['Type'] ?? null) === 'SpamComplaint'
                ? CampaignEventType::Complained
                : CampaignEventType::Bounced,
            'SubscriptionChange' => ($payload['SuppressSending'] ?? false) ? CampaignEventType::Unsubscribed : null,
            default => null,
        };
    }

    protected function formatAddress(string $email, ?string $name): string
    {
        return $name ? "{$name} <{$email}>" : $email;
    }
}
