<?php

use DanPascu\FilamentEmailCampaigns\Drivers\SendGridDriver;
use DanPascu\FilamentEmailCampaigns\Enums\CampaignEventType;
use DanPascu\FilamentEmailCampaigns\Enums\EspProvider;
use DanPascu\FilamentEmailCampaigns\Models\EmailProvider;
use DanPascu\FilamentEmailCampaigns\Support\EspMessage;
use DanPascu\FilamentEmailCampaigns\Tests\Fixtures\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

function makeSendGridProvider(?string $publicKey = null): EmailProvider
{
    $tenant = Tenant::create(['name' => 'Acme']);

    return EmailProvider::create([
        'tenant_id' => $tenant->id,
        'name' => 'SendGrid',
        'provider' => EspProvider::SendGrid->value,
        'credentials' => ['api_key' => 'sg-api-key'],
        'webhook_signing_secret' => $publicKey,
    ]);
}

/**
 * Generates an EC P-256 key pair, signs $payload with the private key,
 * and returns [bareBase64PublicKey, base64Signature] — matching the
 * format SendGrid's dashboard and webhook headers actually use.
 */
function signSendGridPayload(string $payload): array
{
    $keyPair = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
    openssl_pkey_export($keyPair, $privateKeyPem);
    $publicKeyPem = openssl_pkey_get_details($keyPair)['key'];

    $bareBase64PublicKey = trim(str_replace(
        ["-----BEGIN PUBLIC KEY-----", "-----END PUBLIC KEY-----", "\n"],
        '',
        $publicKeyPem,
    ));

    openssl_sign($payload, $signature, $privateKeyPem, OPENSSL_ALGO_SHA256);

    return [$bareBase64PublicKey, base64_encode($signature)];
}

it('sends through the SendGrid mail/send API with a bearer token', function () {
    Http::fake(['*' => Http::response('', 202, ['X-Message-Id' => 'sg-msg-1.filter'])]);

    $result = (new SendGridDriver(makeSendGridProvider()))->send(new EspMessage(
        toEmail: 'a@acme.com',
        toName: 'Ann',
        fromEmail: 'hello@acme.com',
        fromName: 'Acme',
        subject: 'Hi',
        html: '<p>Hi</p>',
        tags: ['campaign-1'],
        metadata: ['send_id' => '42'],
    ));

    expect($result->successful)->toBeTrue();
    expect($result->messageId)->toBe('sg-msg-1.filter');

    Http::assertSent(fn ($request) => $request->url() === 'https://api.sendgrid.com/v3/mail/send'
        && $request->header('Authorization')[0] === 'Bearer sg-api-key'
        && $request->data()['personalizations'][0]['custom_args']['send_id'] === '42'
        && $request->data()['categories'] === ['campaign-1']);
});

it('returns a failure result when SendGrid responds with errors', function () {
    Http::fake(['*' => Http::response(['errors' => [['message' => 'Invalid from address']]], 400)]);

    $result = (new SendGridDriver(makeSendGridProvider()))->send(new EspMessage(
        toEmail: 'a@acme.com', toName: null, fromEmail: 'hello@acme.com', fromName: 'Acme', subject: 'Hi', html: '<p>Hi</p>',
    ));

    expect($result->successful)->toBeFalse();
    expect($result->errorMessage)->toBe('Invalid from address');
});

it('verifies a correctly signed event webhook', function () {
    $body = json_encode([['event' => 'delivered', 'sg_message_id' => 'sg-1', 'timestamp' => 1767225600]]);
    $timestamp = '1767225600';

    [$publicKey, $signature] = signSendGridPayload($timestamp.$body);

    $driver = new SendGridDriver(makeSendGridProvider($publicKey));
    $request = Request::create('/webhooks/1', 'POST', content: $body, server: [
        'HTTP_X_TWILIO_EMAIL_EVENT_WEBHOOK_SIGNATURE' => $signature,
        'HTTP_X_TWILIO_EMAIL_EVENT_WEBHOOK_TIMESTAMP' => $timestamp,
    ]);

    expect($driver->verifyWebhookRequest($request))->toBeTrue();
});

it('rejects a tampered event webhook body', function () {
    $body = json_encode([['event' => 'delivered', 'sg_message_id' => 'sg-1', 'timestamp' => 1767225600]]);
    $timestamp = '1767225600';

    [$publicKey, $signature] = signSendGridPayload($timestamp.$body);

    $tamperedBody = json_encode([['event' => 'bounce', 'sg_message_id' => 'sg-1', 'timestamp' => 1767225600]]);

    $driver = new SendGridDriver(makeSendGridProvider($publicKey));
    $request = Request::create('/webhooks/1', 'POST', content: $tamperedBody, server: [
        'HTTP_X_TWILIO_EMAIL_EVENT_WEBHOOK_SIGNATURE' => $signature,
        'HTTP_X_TWILIO_EMAIL_EVENT_WEBHOOK_TIMESTAMP' => $timestamp,
    ]);

    expect($driver->verifyWebhookRequest($request))->toBeFalse();
});

it('parses a batch of webhook events, skipping unrecognized types', function () {
    $driver = new SendGridDriver(makeSendGridProvider('whatever'));
    $events = [
        ['event' => 'delivered', 'sg_message_id' => 'sg-1', 'timestamp' => 1767225600, 'send_id' => '42'],
        ['event' => 'click', 'sg_message_id' => 'sg-1', 'timestamp' => 1767225600, 'url' => 'https://acme.com/offer'],
        ['event' => 'processed', 'sg_message_id' => 'sg-1', 'timestamp' => 1767225600],
    ];

    $request = Request::create('/webhooks/1', 'POST', content: json_encode($events));
    $parsed = $driver->parseWebhookRequest($request);

    expect($parsed)->toHaveCount(2);
    expect($parsed[0]->type)->toBe(CampaignEventType::Delivered);
    expect($parsed[0]->meta['send_id'])->toBe('42');
    expect($parsed[1]->type)->toBe(CampaignEventType::Clicked);
    expect($parsed[1]->clickedUrl)->toBe('https://acme.com/offer');
});
