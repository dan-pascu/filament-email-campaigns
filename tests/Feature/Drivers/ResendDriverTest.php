<?php

use DanPascu\FilamentEmailCampaigns\Drivers\ResendDriver;
use DanPascu\FilamentEmailCampaigns\Enums\CampaignEventType;
use DanPascu\FilamentEmailCampaigns\Enums\EspProvider;
use DanPascu\FilamentEmailCampaigns\Models\EmailProvider;
use DanPascu\FilamentEmailCampaigns\Support\EspMessage;
use DanPascu\FilamentEmailCampaigns\Tests\Fixtures\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

function makeResendProvider(?string $webhookSecret = null): EmailProvider
{
    $tenant = Tenant::create(['name' => 'Acme']);

    return EmailProvider::create([
        'tenant_id' => $tenant->id,
        'name' => 'Resend',
        'provider' => EspProvider::Resend->value,
        'credentials' => ['api_key' => 'resend-api-key'],
        'webhook_signing_secret' => $webhookSecret,
    ]);
}

/**
 * @return array{0: string, 1: Request} [secret, signedRequest]
 */
function signResendWebhook(string $body): array
{
    $secretBytes = random_bytes(32);
    $secret = 'whsec_'.base64_encode($secretBytes);

    $id = 'msg_123';
    $timestamp = (string) 1767225600;
    $signature = base64_encode(hash_hmac('sha256', "{$id}.{$timestamp}.{$body}", $secretBytes, true));

    $request = Request::create('/webhooks/1', 'POST', content: $body, server: [
        'HTTP_SVIX_ID' => $id,
        'HTTP_SVIX_TIMESTAMP' => $timestamp,
        'HTTP_SVIX_SIGNATURE' => "v1,{$signature}",
    ]);

    return [$secret, $request];
}

it('sends through the Resend API with a bearer token', function () {
    Http::fake(['*' => Http::response(['id' => 'resend-1'], 200)]);

    $result = (new ResendDriver(makeResendProvider()))->send(new EspMessage(
        toEmail: 'a@acme.com',
        toName: 'Ann',
        fromEmail: 'hello@acme.com',
        fromName: 'Acme',
        subject: 'Hi',
        html: '<p>Hi</p>',
        metadata: ['send_id' => '42'],
    ));

    expect($result->successful)->toBeTrue();
    expect($result->messageId)->toBe('resend-1');

    Http::assertSent(fn ($request) => $request->url() === 'https://api.resend.com/emails'
        && $request->header('Authorization')[0] === 'Bearer resend-api-key'
        && $request->data()['from'] === 'Acme <hello@acme.com>'
        && in_array(['name' => 'send_id', 'value' => '42'], $request->data()['tags'] ?? [], true));
});

it('returns a failure result when Resend responds with an error', function () {
    Http::fake(['*' => Http::response(['message' => 'Invalid `from` field'], 422)]);

    $result = (new ResendDriver(makeResendProvider()))->send(new EspMessage(
        toEmail: 'a@acme.com', toName: null, fromEmail: 'hello@acme.com', fromName: 'Acme', subject: 'Hi', html: '<p>Hi</p>',
    ));

    expect($result->successful)->toBeFalse();
    expect($result->errorMessage)->toBe('Invalid `from` field');
});

it('verifies a correctly signed Svix webhook', function () {
    $body = json_encode(['type' => 'email.delivered', 'data' => ['email_id' => 'resend-1']]);
    [$secret, $request] = signResendWebhook($body);

    $driver = new ResendDriver(makeResendProvider($secret));

    expect($driver->verifyWebhookRequest($request))->toBeTrue();
});

it('rejects a tampered Svix webhook body', function () {
    $body = json_encode(['type' => 'email.delivered', 'data' => ['email_id' => 'resend-1']]);
    [$secret, $request] = signResendWebhook($body);

    $tamperedRequest = Request::create('/webhooks/1', 'POST', content: json_encode(['type' => 'email.bounced', 'data' => ['email_id' => 'resend-1']]), server: [
        'HTTP_SVIX_ID' => $request->header('svix-id'),
        'HTTP_SVIX_TIMESTAMP' => $request->header('svix-timestamp'),
        'HTTP_SVIX_SIGNATURE' => $request->header('svix-signature'),
    ]);

    $driver = new ResendDriver(makeResendProvider($secret));

    expect($driver->verifyWebhookRequest($tamperedRequest))->toBeFalse();
});

it('rejects a webhook when no signing secret is configured', function () {
    $body = json_encode(['type' => 'email.delivered', 'data' => ['email_id' => 'resend-1']]);
    [, $request] = signResendWebhook($body);

    $driver = new ResendDriver(makeResendProvider(null));

    expect($driver->verifyWebhookRequest($request))->toBeFalse();
});

it('parses a delivered event with the send_id tag', function () {
    $driver = new ResendDriver(makeResendProvider());
    $request = Request::create('/webhooks/1', 'POST', content: json_encode([
        'type' => 'email.delivered',
        'created_at' => '2026-01-01T00:00:00.000Z',
        'data' => ['email_id' => 'resend-1', 'tags' => [['name' => 'send_id', 'value' => '42']]],
    ]));

    $events = $driver->parseWebhookRequest($request);

    expect($events[0]->type)->toBe(CampaignEventType::Delivered);
    expect($events[0]->messageId)->toBe('resend-1');
    expect($events[0]->meta['send_id'])->toBe('42');
});

it('parses a clicked event with the clicked link', function () {
    $driver = new ResendDriver(makeResendProvider());
    $request = Request::create('/webhooks/1', 'POST', content: json_encode([
        'type' => 'email.clicked',
        'created_at' => '2026-01-01T00:00:00.000Z',
        'data' => ['email_id' => 'resend-1', 'click' => ['link' => 'https://acme.com/offer']],
    ]));

    $events = $driver->parseWebhookRequest($request);

    expect($events[0]->type)->toBe(CampaignEventType::Clicked);
    expect($events[0]->clickedUrl)->toBe('https://acme.com/offer');
});

it('maps bounced and complained events', function () {
    $driver = new ResendDriver(makeResendProvider());

    $makeRequest = fn (string $type) => Request::create('/webhooks/1', 'POST', content: json_encode([
        'type' => $type, 'created_at' => '2026-01-01T00:00:00.000Z', 'data' => ['email_id' => 'resend-1'],
    ]));

    expect($driver->parseWebhookRequest($makeRequest('email.bounced'))[0]->type)->toBe(CampaignEventType::Bounced);
    expect($driver->parseWebhookRequest($makeRequest('email.complained'))[0]->type)->toBe(CampaignEventType::Complained);
});

it('returns no events for an untracked event type', function () {
    $driver = new ResendDriver(makeResendProvider());
    $request = Request::create('/webhooks/1', 'POST', content: json_encode([
        'type' => 'email.delivery_delayed', 'data' => ['email_id' => 'resend-1'],
    ]));

    expect($driver->parseWebhookRequest($request))->toBe([]);
});
