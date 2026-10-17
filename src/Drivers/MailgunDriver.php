<?php

namespace DanPascu\FilamentEmailCampaigns\Drivers;

use DanPascu\FilamentEmailCampaigns\Contracts\EspDriver;
use DanPascu\FilamentEmailCampaigns\Enums\CampaignEventType;
use DanPascu\FilamentEmailCampaigns\Models\EmailProvider;
use DanPascu\FilamentEmailCampaigns\Support\EspMessage;
use DanPascu\FilamentEmailCampaigns\Support\EspSendResult;
use DanPascu\FilamentEmailCampaigns\Support\EspWebhookEvent;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MailgunDriver implements EspDriver
{
    protected Client $client;

    public function __construct(protected EmailProvider $provider)
    {
        $this->client = new Client([
            'base_uri' => $this->apiBaseUri(),
            'auth' => ['api', (string) $provider->credential('api_key')],
        ]);
    }

    public static function credentialFields(): array
    {
        return [
            'domain' => 'Sending domain (e.g. mail.example.com)',
            'api_key' => 'Private API key',
            'region' => 'Region ("us" or "eu")',
        ];
    }

    public function send(EspMessage $message): EspSendResult
    {
        $domain = (string) $this->provider->credential('domain');

        $form = [
            'from' => $this->formatAddress($message->fromEmail, $message->fromName),
            'to' => $this->formatAddress($message->toEmail, $message->toName),
            'subject' => $message->subject,
            'html' => $message->html,
        ];

        if ($message->replyToEmail) {
            $form['h:Reply-To'] = $message->replyToEmail;
        }

        foreach ($message->tags as $tag) {
            $form['o:tag'][] = $tag;
        }

        foreach ($message->metadata as $key => $value) {
            $form["v:{$key}"] = is_scalar($value) ? (string) $value : json_encode($value);
        }

        try {
            $response = $this->client->post("/v3/{$domain}/messages", [
                'form_params' => $form,
            ]);
        } catch (GuzzleException $exception) {
            return EspSendResult::failure($exception->getMessage());
        }

        $body = json_decode((string) $response->getBody(), true) ?? [];

        return EspSendResult::success((string) ($body['id'] ?? ''));
    }

    public function verifyWebhookRequest(Request $request): bool
    {
        $signature = $request->input('signature', []);
        $secret = $this->provider->webhook_signing_secret;

        if (! $secret || ! isset($signature['timestamp'], $signature['token'], $signature['signature'])) {
            return false;
        }

        $expected = hash_hmac('sha256', $signature['timestamp'].$signature['token'], $secret);

        return hash_equals($expected, (string) $signature['signature']);
    }

    public function parseWebhookRequest(Request $request): array
    {
        $eventData = $request->input('event-data', []);
        $type = $this->mapEventType($eventData);

        if ($type === null) {
            return [];
        }

        $occurredAt = isset($eventData['timestamp'])
            ? Carbon::createFromTimestamp((float) $eventData['timestamp'])
            : Carbon::now();

        $userVariables = $eventData['user-variables'] ?? [];
        $messageId = $eventData['message']['headers']['message-id'] ?? null;

        return [
            new EspWebhookEvent(
                type: $type,
                messageId: $messageId,
                occurredAt: $occurredAt,
                meta: [
                    'send_id' => $userVariables['send_id'] ?? null,
                    'reason' => $eventData['reason'] ?? $eventData['severity'] ?? null,
                ],
                clickedUrl: $eventData['url'] ?? null,
            ),
        ];
    }

    protected function mapEventType(array $eventData): ?CampaignEventType
    {
        $event = $eventData['event'] ?? null;

        return match ($event) {
            'delivered' => CampaignEventType::Delivered,
            'opened' => CampaignEventType::Opened,
            'clicked' => CampaignEventType::Clicked,
            'unsubscribed' => CampaignEventType::Unsubscribed,
            'complained' => CampaignEventType::Complained,
            'failed' => ($eventData['severity'] ?? null) === 'permanent'
                ? CampaignEventType::Bounced
                : CampaignEventType::Failed,
            default => null,
        };
    }

    protected function formatAddress(string $email, ?string $name): string
    {
        return $name ? "{$name} <{$email}>" : $email;
    }

    protected function apiBaseUri(): string
    {
        return $this->provider->credential('region') === 'eu'
            ? 'https://api.eu.mailgun.net'
            : 'https://api.mailgun.net';
    }
}
