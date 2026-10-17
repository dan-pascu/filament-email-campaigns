<?php

namespace DanPascu\FilamentEmailCampaigns\Drivers;

use DanPascu\FilamentEmailCampaigns\Contracts\EspDriver;
use DanPascu\FilamentEmailCampaigns\Enums\CampaignEventType;
use DanPascu\FilamentEmailCampaigns\Models\EmailProvider;
use DanPascu\FilamentEmailCampaigns\Support\AwsSigV4Signer;
use DanPascu\FilamentEmailCampaigns\Support\EspMessage;
use DanPascu\FilamentEmailCampaigns\Support\EspSendResult;
use DanPascu\FilamentEmailCampaigns\Support\EspWebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Sends through Amazon SES's v2 HTTP API directly (SigV4-signed, no AWS
 * SDK). Delivery/bounce/complaint/open/click events arrive as Amazon SNS
 * HTTP notifications wrapping the SES event JSON; verifying them means
 * checking the SNS envelope's own signature against its signing
 * certificate (fetched from the amazonaws.com URL SNS includes).
 */
class SesDriver implements EspDriver
{
    public function __construct(protected EmailProvider $provider) {}

    public static function credentialFields(): array
    {
        return [
            'access_key_id' => 'AWS access key ID',
            'secret_access_key' => 'AWS secret access key',
            'aws_region' => 'AWS region (e.g. us-east-1)',
        ];
    }

    public function send(EspMessage $message): EspSendResult
    {
        $region = (string) $this->provider->credential('aws_region', 'us-east-1');
        $host = "email.{$region}.amazonaws.com";

        $payload = [
            'FromEmailAddress' => $this->formatAddress($message->fromEmail, $message->fromName),
            'Destination' => ['ToAddresses' => [$this->formatAddress($message->toEmail, $message->toName)]],
            'Content' => ['Simple' => [
                'Subject' => ['Data' => $message->subject, 'Charset' => 'UTF-8'],
                'Body' => ['Html' => ['Data' => $message->html, 'Charset' => 'UTF-8']],
            ]],
        ];

        if ($message->replyToEmail) {
            $payload['ReplyToAddresses'] = [$message->replyToEmail];
        }

        $tags = $this->buildTags($message);

        if ($tags !== []) {
            $payload['EmailTags'] = $tags;
        }

        $body = (string) json_encode($payload);

        $signer = new AwsSigV4Signer(
            (string) $this->provider->credential('access_key_id'),
            (string) $this->provider->credential('secret_access_key'),
            $region,
            'ses',
        );

        $signedHeaders = $signer->sign('POST', $host, '/v2/email/outbound-emails', $body);

        $response = Http::withHeaders($signedHeaders)
            ->withBody($body, 'application/json')
            ->post("https://{$host}/v2/email/outbound-emails");

        if ($response->failed()) {
            return EspSendResult::failure((string) ($response->json('message') ?? $response->body()));
        }

        return EspSendResult::success((string) $response->json('MessageId'));
    }

    public function verifyWebhookRequest(Request $request): bool
    {
        $envelope = json_decode($request->getContent(), true);

        if (! is_array($envelope) || ! isset($envelope['SigningCertURL'], $envelope['Signature'], $envelope['Type'])) {
            return false;
        }

        if (! preg_match('#^https://sns\.[a-z0-9-]+\.amazonaws\.com/#i', (string) $envelope['SigningCertURL'])) {
            return false;
        }

        $certPem = Http::get($envelope['SigningCertURL'])->body();

        if ($certPem === '') {
            return false;
        }

        $stringToSign = $this->buildStringToSign($envelope);
        $signature = base64_decode((string) $envelope['Signature'], strict: true);

        if ($stringToSign === null || $signature === false) {
            return false;
        }

        $algorithm = ($envelope['SignatureVersion'] ?? '1') === '2' ? OPENSSL_ALGO_SHA256 : OPENSSL_ALGO_SHA1;

        return openssl_verify($stringToSign, $signature, $certPem, $algorithm) === 1;
    }

    public function parseWebhookRequest(Request $request): array
    {
        $envelope = json_decode($request->getContent(), true);

        if (! is_array($envelope)) {
            return [];
        }

        $type = $envelope['Type'] ?? null;

        if ($type === 'SubscriptionConfirmation' && isset($envelope['SubscribeURL'])) {
            Http::get($envelope['SubscribeURL']);

            return [];
        }

        if ($type !== 'Notification' || ! isset($envelope['Message'])) {
            return [];
        }

        $sesEvent = json_decode((string) $envelope['Message'], true);

        if (! is_array($sesEvent)) {
            return [];
        }

        $eventType = $this->mapEventType($sesEvent['eventType'] ?? null);

        if ($eventType === null) {
            return [];
        }

        $mail = $sesEvent['mail'] ?? [];
        $messageId = $mail['messageId'] ?? null;
        $sendId = $mail['tags']['send_id'][0] ?? null;

        $sub = match ($sesEvent['eventType']) {
            'Bounce' => $sesEvent['bounce'] ?? [],
            'Complaint' => $sesEvent['complaint'] ?? [],
            'Delivery' => $sesEvent['delivery'] ?? [],
            'Open' => $sesEvent['open'] ?? [],
            'Click' => $sesEvent['click'] ?? [],
            default => [],
        };

        $occurredAt = isset($sub['timestamp'])
            ? Carbon::parse($sub['timestamp'])
            : Carbon::parse($envelope['Timestamp'] ?? now());

        return [
            new EspWebhookEvent(
                type: $eventType,
                messageId: $messageId,
                occurredAt: $occurredAt,
                meta: ['send_id' => $sendId],
                clickedUrl: $sub['link'] ?? null,
            ),
        ];
    }

    protected function mapEventType(?string $event): ?CampaignEventType
    {
        return match ($event) {
            'Send' => CampaignEventType::Sent,
            'Delivery' => CampaignEventType::Delivered,
            'Open' => CampaignEventType::Opened,
            'Click' => CampaignEventType::Clicked,
            'Bounce' => CampaignEventType::Bounced,
            'Complaint' => CampaignEventType::Complained,
            'Reject' => CampaignEventType::Failed,
            default => null,
        };
    }

    /**
     * @return array<int, array{Name: string, Value: string}>
     */
    protected function buildTags(EspMessage $message): array
    {
        $tags = [];

        foreach ($message->tags as $index => $tag) {
            $tags[] = ['Name' => 'tag_'.$index, 'Value' => $this->sanitizeTagValue($tag)];
        }

        foreach ($message->metadata as $key => $value) {
            $tags[] = ['Name' => $this->sanitizeTagValue((string) $key), 'Value' => $this->sanitizeTagValue((string) $value)];
        }

        return $tags;
    }

    protected function sanitizeTagValue(string $value): string
    {
        return substr((string) preg_replace('/[^a-zA-Z0-9_-]/', '_', $value), 0, 256);
    }

    protected function formatAddress(string $email, ?string $name): string
    {
        return $name ? "{$name} <{$email}>" : $email;
    }

    /**
     * Builds the SNS canonical string-to-sign for a Notification or
     * SubscriptionConfirmation/UnsubscribeConfirmation envelope.
     *
     * @param  array<string, mixed>  $envelope
     */
    protected function buildStringToSign(array $envelope): ?string
    {
        $fields = $envelope['Type'] === 'Notification'
            ? ['Message', 'MessageId', 'Subject', 'Timestamp', 'TopicArn', 'Type']
            : ['Message', 'MessageId', 'SubscribeURL', 'Timestamp', 'Token', 'TopicArn', 'Type'];

        $string = '';

        foreach ($fields as $field) {
            if (! array_key_exists($field, $envelope)) {
                continue;
            }

            $string .= $field."\n".$envelope[$field]."\n";
        }

        return $string === '' ? null : $string;
    }
}
