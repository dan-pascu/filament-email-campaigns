<?php

use DanPascu\FilamentEmailCampaigns\Drivers\BrevoDriver;
use DanPascu\FilamentEmailCampaigns\Enums\CampaignEventType;
use DanPascu\FilamentEmailCampaigns\Enums\EspProvider;
use DanPascu\FilamentEmailCampaigns\Models\EmailProvider;
use DanPascu\FilamentEmailCampaigns\Support\EspMessage;
use DanPascu\FilamentEmailCampaigns\Tests\Fixtures\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

function makeBrevoProvider(?string $webhookSecret = 'brevo-secret'): EmailProvider
{
    $tenant = Tenant::create(['name' => 'Acme']);

    return EmailProvider::create([
        'tenant_id' => $tenant->id,
        'name' => 'Brevo',
        'provider' => EspProvider::Brevo->value,
        'credentials' => ['api_key' => 'brevo-api-key'],
        'webhook_signing_secret' => $webhookSecret,
    ]);
}

it('sends through the Brevo transactional email API with the api-key header', function () {
    Http::fake(['*' => Http::response(['messageId' => '<brevo-1@smtp-relay.mailin.fr>'], 201)]);

    $result = (new BrevoDriver(makeBrevoProvider()))->send(new EspMessage(
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
    expect($result->messageId)->toBe('<brevo-1@smtp-relay.mailin.fr>');

    Http::assertSent(fn ($request) => $request->url() === 'https://api.brevo.com/v3/smtp/email'
        && $request->header('api-key')[0] === 'brevo-api-key'
        && $request->data()['tags'] === ['campaign-1', 'send:42']);
});

it('returns a failure result when Brevo responds with an error', function () {
    Http::fake(['*' => Http::response(['code' => 'invalid_parameter', 'message' => 'Invalid sender'], 400)]);

    $result = (new BrevoDriver(makeBrevoProvider()))->send(new EspMessage(
        toEmail: 'a@acme.com', toName: null, fromEmail: 'hello@acme.com', fromName: 'Acme', subject: 'Hi', html: '<p>Hi</p>',
    ));

    expect($result->successful)->toBeFalse();
    expect($result->errorMessage)->toBe('Invalid sender');
});

it('verifies webhooks using the token query parameter', function () {
    $driver = new BrevoDriver(makeBrevoProvider());

    $valid = Request::create('/webhooks/1?token=brevo-secret', 'POST', content: '{}');
    $invalid = Request::create('/webhooks/1?token=wrong', 'POST', content: '{}');

    expect($driver->verifyWebhookRequest($valid))->toBeTrue();
    expect($driver->verifyWebhookRequest($invalid))->toBeFalse();
});

it('rejects webhooks when no signing secret is configured', function () {
    $driver = new BrevoDriver(makeBrevoProvider(webhookSecret: null));

    expect($driver->verifyWebhookRequest(Request::create('/webhooks/1?token=anything', 'POST')))->toBeFalse();
});

it('parses a delivered webhook event with the send_id tag', function () {
    $driver = new BrevoDriver(makeBrevoProvider());
    $request = Request::create('/webhooks/1', 'POST', content: json_encode([
        'event' => 'delivered',
        'email' => 'a@acme.com',
        'message-id' => '<brevo-1@smtp-relay.mailin.fr>',
        'ts' => 1767225600,
        'tags' => ['campaign-1', 'send:42'],
    ]), server: ['CONTENT_TYPE' => 'application/json']);

    $events = $driver->parseWebhookRequest($request);

    expect($events[0]->type)->toBe(CampaignEventType::Delivered);
    expect($events[0]->messageId)->toBe('<brevo-1@smtp-relay.mailin.fr>');
    expect($events[0]->meta['send_id'])->toBe('42');
});

it('parses a click webhook with the clicked link', function () {
    $driver = new BrevoDriver(makeBrevoProvider());
    $request = Request::create('/webhooks/1', 'POST', content: json_encode([
        'event' => 'click',
        'link' => 'https://acme.com/offer',
        'ts' => 1767225600,
    ]), server: ['CONTENT_TYPE' => 'application/json']);

    $events = $driver->parseWebhookRequest($request);

    expect($events[0]->type)->toBe(CampaignEventType::Clicked);
    expect($events[0]->clickedUrl)->toBe('https://acme.com/offer');
});

it('maps bounce, spam, and unsubscribe events', function () {
    $driver = new BrevoDriver(makeBrevoProvider());

    $makeRequest = fn (string $event) => Request::create('/webhooks/1', 'POST', content: json_encode(['event' => $event, 'ts' => 1767225600]), server: ['CONTENT_TYPE' => 'application/json']);

    expect($driver->parseWebhookRequest($makeRequest('hard_bounce'))[0]->type)->toBe(CampaignEventType::Bounced);
    expect($driver->parseWebhookRequest($makeRequest('spam'))[0]->type)->toBe(CampaignEventType::Complained);
    expect($driver->parseWebhookRequest($makeRequest('unsubscribed'))[0]->type)->toBe(CampaignEventType::Unsubscribed);
});

it('returns no events for an untracked event type', function () {
    $driver = new BrevoDriver(makeBrevoProvider());
    $request = Request::create('/webhooks/1', 'POST', content: json_encode(['event' => 'request', 'ts' => 1767225600]), server: ['CONTENT_TYPE' => 'application/json']);

    expect($driver->parseWebhookRequest($request))->toBe([]);
});
