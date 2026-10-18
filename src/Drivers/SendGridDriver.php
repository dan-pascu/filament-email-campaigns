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
 * SendGrid's Event Webhook sends a batch (JSON array) of events per
 * request, and signs the whole request with ECDSA (P-256/SHA-256); the
 * public key configured in SendGrid's dashboard is stored, as the base64
 * string SendGrid gives you, on EmailProvider::webhook_signing_secret.
 */
class SendGridDriver implements EspDriver
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
        $personalization = [
            'to' => [array_filter(['email' => $message->toEmail, 'name' => $message->toName])],
        ];

        if ($message->metadata !== []) {
            $personalization['custom_args'] = array_map('strval', $message->metadata);
        }

        $payload = [
            'personalizations' => [$personalization],
            'from' => array_filter(['email' => $message->fromEmail, 'name' => $message->fromName]),
            'subject' => $message->subject,
            'content' => [['type' => 'text/html', 'value' => $message->html]],
        ];

        if ($message->replyToEmail) {
            $payload['reply_to'] = ['email' => $message->replyToEmail];
        }

        if ($message->tags !== []) {
            $payload['categories'] = $message->tags;
        }

        $response = Http::withToken((string) $this->provider->credential('api_key'))
            ->post('https://api.sendgrid.com/v3/mail/send', $payload);

        if ($response->failed()) {
            $errors = $response->json('errors') ?? [];
            $message = implode('; ', array_column($errors, 'message')) ?: $response->body();

            return EspSendResult::failure($message);
        }

        return EspSendResult::success((string) $response->header('X-Message-Id'));
    }

    public function verifyWebhookRequest(Request $request): bool
    {
        $publicKey = $this->provider->webhook_signing_secret;

        if (! $publicKey) {
            return false;
        }

        $signature = $request->header('X-Twilio-Email-Event-Webhook-Signature');
        $timestamp = $request->header('X-Twilio-Email-Event-Webhook-Timestamp');

        if (! $signature || ! $timestamp) {
            return false;
        }

        $pem = "-----BEGIN PUBLIC KEY-----\n".wordwrap($publicKey, 64, "\n", true)."\n-----END PUBLIC KEY-----\n";
        $payload = $timestamp.$request->getContent();

        return openssl_verify($payload, (string) base64_decode($signature, strict: true), $pem, OPENSSL_ALGO_SHA256) === 1;
    }

    public function parseWebhookRequest(Request $request): array
    {
        $events = json_decode($request->getContent(), true);

        if (! is_array($events)) {
            return [];
        }

        $results = [];

        foreach ($events as $event) {
            $type = $this->mapEventType($event['event'] ?? null);

            if ($type === null) {
                continue;
            }

            $results[] = new EspWebhookEvent(
                type: $type,
                messageId: $event['sg_message_id'] ?? null,
                occurredAt: isset($event['timestamp']) ? Carbon::createFromTimestamp((int) $event['timestamp']) : Carbon::now(),
                meta: ['send_id' => $event['send_id'] ?? null],
                clickedUrl: $event['url'] ?? null,
            );
        }

        return $results;
    }

    protected function mapEventType(?string $event): ?CampaignEventType
    {
        return match ($event) {
            'delivered' => CampaignEventType::Delivered,
            'open' => CampaignEventType::Opened,
            'click' => CampaignEventType::Clicked,
            'bounce' => CampaignEventType::Bounced,
            'spamreport' => CampaignEventType::Complained,
            'unsubscribe', 'group_unsubscribe' => CampaignEventType::Unsubscribed,
            'dropped' => CampaignEventType::Failed,
            default => null,
        };
    }
}
