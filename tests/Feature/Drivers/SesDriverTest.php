<?php

use DanPascu\FilamentEmailCampaigns\Drivers\SesDriver;
use DanPascu\FilamentEmailCampaigns\Enums\CampaignEventType;
use DanPascu\FilamentEmailCampaigns\Enums\EspProvider;
use DanPascu\FilamentEmailCampaigns\Models\EmailProvider;
use DanPascu\FilamentEmailCampaigns\Support\EspMessage;
use DanPascu\FilamentEmailCampaigns\Tests\Fixtures\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

function makeSesProvider(): EmailProvider
{
    $tenant = Tenant::create(['name' => 'Acme']);

    return EmailProvider::create([
        'tenant_id' => $tenant->id,
        'name' => 'SES',
        'provider' => EspProvider::Ses->value,
        'credentials' => [
            'access_key_id' => 'AKIAFAKE',
            'secret_access_key' => 'fakesecret',
            'aws_region' => 'us-east-1',
        ],
    ]);
}

/**
 * Signs an SNS envelope with a freshly generated RSA key pair and returns
 * [envelope, publicKeyPem], matching what a real SigningCertURL fetch
 * would return (openssl_verify() accepts a bare public key PEM as well
 * as a full certificate).
 */
function signSnsEnvelope(array $envelope): array
{
    $keyPair = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    openssl_pkey_export($keyPair, $privateKeyPem);
    $publicKeyPem = openssl_pkey_get_details($keyPair)['key'];

    $fields = $envelope['Type'] === 'Notification'
        ? ['Message', 'MessageId', 'Subject', 'Timestamp', 'TopicArn', 'Type']
        : ['Message', 'MessageId', 'SubscribeURL', 'Timestamp', 'Token', 'TopicArn', 'Type'];

    $stringToSign = '';
    foreach ($fields as $field) {
        if (array_key_exists($field, $envelope)) {
            $stringToSign .= $field."\n".$envelope[$field]."\n";
        }
    }

    openssl_sign($stringToSign, $signature, $privateKeyPem, OPENSSL_ALGO_SHA1);
    $envelope['Signature'] = base64_encode($signature);
    $envelope['SignatureVersion'] = '1';
    $envelope['SigningCertURL'] = 'https://sns.us-east-1.amazonaws.com/SimpleNotificationService-fake.pem';

    return [$envelope, $publicKeyPem];
}

it('sends through the SESv2 API with a SigV4 Authorization header', function () {
    Http::fake([
        '*' => Http::response(['MessageId' => 'ses-message-1'], 200),
    ]);

    $provider = makeSesProvider();
    $driver = new SesDriver($provider);

    $result = $driver->send(new EspMessage(
        toEmail: 'a@acme.com',
        toName: 'Ann',
        fromEmail: 'hello@acme.com',
        fromName: 'Acme',
        subject: 'Hello',
        html: '<p>Hi</p>',
        metadata: ['send_id' => '42'],
    ));

    expect($result->successful)->toBeTrue();
    expect($result->messageId)->toBe('ses-message-1');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://email.us-east-1.amazonaws.com/v2/email/outbound-emails'
            && str_starts_with($request->header('Authorization')[0] ?? '', 'AWS4-HMAC-SHA256 Credential=AKIAFAKE/')
            && ($request->data()['FromEmailAddress'] ?? null) === 'Acme <hello@acme.com>'
            && in_array(['Name' => 'send_id', 'Value' => '42'], $request->data()['EmailTags'] ?? [], true);
    });
});

it('returns a failure result when SES responds with an error', function () {
    Http::fake(['*' => Http::response(['message' => 'Email address not verified'], 400)]);

    $driver = new SesDriver(makeSesProvider());
    $result = $driver->send(new EspMessage(toEmail: 'a@acme.com', toName: null, fromEmail: 'hello@acme.com', fromName: 'Acme', subject: 'Hi', html: '<p>Hi</p>'));

    expect($result->successful)->toBeFalse();
    expect($result->errorMessage)->toBe('Email address not verified');
});

it('verifies a correctly signed SNS notification', function () {
    [$envelope, $publicKeyPem] = signSnsEnvelope([
        'Type' => 'Notification',
        'MessageId' => 'sns-1',
        'TopicArn' => 'arn:aws:sns:us-east-1:111111111111:ses-events',
        'Message' => json_encode(['eventType' => 'Delivery', 'mail' => ['messageId' => 'ses-msg-1']]),
        'Timestamp' => '2026-01-01T00:00:00.000Z',
    ]);

    Http::fake(['*' => Http::response($publicKeyPem, 200)]);

    $driver = new SesDriver(makeSesProvider());
    $request = Request::create('/webhooks/1', 'POST', content: json_encode($envelope));

    expect($driver->verifyWebhookRequest($request))->toBeTrue();
});

it('rejects an SNS notification with a tampered signature', function () {
    [$envelope, $publicKeyPem] = signSnsEnvelope([
        'Type' => 'Notification',
        'MessageId' => 'sns-1',
        'TopicArn' => 'arn:aws:sns:us-east-1:111111111111:ses-events',
        'Message' => json_encode(['eventType' => 'Delivery', 'mail' => ['messageId' => 'ses-msg-1']]),
        'Timestamp' => '2026-01-01T00:00:00.000Z',
    ]);

    $envelope['Message'] = json_encode(['eventType' => 'Bounce', 'mail' => ['messageId' => 'ses-msg-1']]);

    Http::fake(['*' => Http::response($publicKeyPem, 200)]);

    $driver = new SesDriver(makeSesProvider());
    $request = Request::create('/webhooks/1', 'POST', content: json_encode($envelope));

    expect($driver->verifyWebhookRequest($request))->toBeFalse();
});

it('rejects a signing cert URL that is not on amazonaws.com', function () {
    $envelope = [
        'Type' => 'Notification',
        'MessageId' => 'sns-1',
        'Message' => '{}',
        'Timestamp' => '2026-01-01T00:00:00.000Z',
        'Signature' => base64_encode('whatever'),
        'SigningCertURL' => 'https://evil.example.com/cert.pem',
    ];

    $driver = new SesDriver(makeSesProvider());
    $request = Request::create('/webhooks/1', 'POST', content: json_encode($envelope));

    expect($driver->verifyWebhookRequest($request))->toBeFalse();
});

it('parses a delivery notification into an EspWebhookEvent with the send_id tag', function () {
    $envelope = [
        'Type' => 'Notification',
        'Message' => json_encode([
            'eventType' => 'Delivery',
            'mail' => ['messageId' => 'ses-msg-1', 'tags' => ['send_id' => ['42']]],
            'delivery' => ['timestamp' => '2026-01-01T00:00:00.000Z'],
        ]),
    ];

    $driver = new SesDriver(makeSesProvider());
    $request = Request::create('/webhooks/1', 'POST', content: json_encode($envelope));

    $events = $driver->parseWebhookRequest($request);

    expect($events)->toHaveCount(1);
    expect($events[0]->type)->toBe(CampaignEventType::Delivered);
    expect($events[0]->messageId)->toBe('ses-msg-1');
    expect($events[0]->meta['send_id'])->toBe('42');
});

it('parses a click notification with the clicked URL', function () {
    $envelope = [
        'Type' => 'Notification',
        'Message' => json_encode([
            'eventType' => 'Click',
            'mail' => ['messageId' => 'ses-msg-1', 'tags' => ['send_id' => ['42']]],
            'click' => ['timestamp' => '2026-01-01T00:00:00.000Z', 'link' => 'https://acme.com/offer'],
        ]),
    ];

    $driver = new SesDriver(makeSesProvider());
    $request = Request::create('/webhooks/1', 'POST', content: json_encode($envelope));

    $events = $driver->parseWebhookRequest($request);

    expect($events[0]->type)->toBe(CampaignEventType::Clicked);
    expect($events[0]->clickedUrl)->toBe('https://acme.com/offer');
});

it('returns no events for an unknown SES event type', function () {
    $envelope = [
        'Type' => 'Notification',
        'Message' => json_encode(['eventType' => 'DeliveryDelay', 'mail' => ['messageId' => 'ses-msg-1']]),
    ];

    $driver = new SesDriver(makeSesProvider());
    $request = Request::create('/webhooks/1', 'POST', content: json_encode($envelope));

    expect($driver->parseWebhookRequest($request))->toBe([]);
});
