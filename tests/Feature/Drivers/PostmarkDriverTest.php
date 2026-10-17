<?php

use DanPascu\FilamentEmailCampaigns\Drivers\PostmarkDriver;
use DanPascu\FilamentEmailCampaigns\Enums\CampaignEventType;
use DanPascu\FilamentEmailCampaigns\Enums\EspProvider;
use DanPascu\FilamentEmailCampaigns\Models\EmailProvider;
use DanPascu\FilamentEmailCampaigns\Support\EspMessage;
use DanPascu\FilamentEmailCampaigns\Tests\Fixtures\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

function makePostmarkProvider(?string $webhookSecret = 'hook:secret'): EmailProvider
{
    $tenant = Tenant::create(['name' => 'Acme']);

    return EmailProvider::create([
        'tenant_id' => $tenant->id,
        'name' => 'Postmark',
        'provider' => EspProvider::Postmark->value,
        'credentials' => ['server_token' => 'server-token-123'],
        'webhook_signing_secret' => $webhookSecret,
    ]);
}

function postmarkWebhookRequest(array $payload, string $user = 'hook', string $password = 'secret'): Request
{
    return Request::create('/webhooks/1', 'POST', content: json_encode($payload), server: [
        'PHP_AUTH_USER' => $user,
        'PHP_AUTH_PW' => $password,
        'CONTENT_TYPE' => 'application/json',
    ]);
}

it('sends through the Postmark API with the server token header', function () {
    Http::fake(['*' => Http::response(['ErrorCode' => 0, 'MessageID' => 'pm-1'], 200)]);

    $result = (new PostmarkDriver(makePostmarkProvider()))->send(new EspMessage(
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
    expect($result->messageId)->toBe('pm-1');

    Http::assertSent(fn ($request) => $request->url() === 'https://api.postmarkapp.com/email'
        && $request->header('X-Postmark-Server-Token')[0] === 'server-token-123'
        && $request->data()['Tag'] === 'campaign-1'
        && $request->data()['Metadata']['send_id'] === '42');
});

it('returns a failure result when Postmark reports an ErrorCode', function () {
    Http::fake(['*' => Http::response(['ErrorCode' => 300, 'Message' => 'Invalid email request'], 200)]);

    $result = (new PostmarkDriver(makePostmarkProvider()))->send(new EspMessage(
        toEmail: 'a@acme.com', toName: null, fromEmail: 'hello@acme.com', fromName: 'Acme', subject: 'Hi', html: '<p>Hi</p>',
    ));

    expect($result->successful)->toBeFalse();
    expect($result->errorMessage)->toBe('Invalid email request');
});

it('verifies webhook requests using HTTP basic auth credentials', function () {
    $driver = new PostmarkDriver(makePostmarkProvider());

    expect($driver->verifyWebhookRequest(postmarkWebhookRequest([])))->toBeTrue();
    expect($driver->verifyWebhookRequest(postmarkWebhookRequest([], password: 'wrong')))->toBeFalse();
});

it('rejects webhooks when no signing secret is configured', function () {
    $driver = new PostmarkDriver(makePostmarkProvider(webhookSecret: null));

    expect($driver->verifyWebhookRequest(postmarkWebhookRequest([])))->toBeFalse();
});

it('parses a delivery webhook', function () {
    $driver = new PostmarkDriver(makePostmarkProvider());
    $request = postmarkWebhookRequest([
        'RecordType' => 'Delivery',
        'MessageID' => 'pm-msg-1',
        'DeliveredAt' => '2026-01-01T00:00:00Z',
        'Metadata' => ['send_id' => '42'],
    ]);

    $events = $driver->parseWebhookRequest($request);

    expect($events[0]->type)->toBe(CampaignEventType::Delivered);
    expect($events[0]->messageId)->toBe('pm-msg-1');
    expect($events[0]->meta['send_id'])->toBe('42');
});

it('parses a hard bounce webhook as Bounced', function () {
    $driver = new PostmarkDriver(makePostmarkProvider());
    $request = postmarkWebhookRequest([
        'RecordType' => 'Bounce',
        'Type' => 'HardBounce',
        'MessageID' => 'pm-msg-1',
        'BouncedAt' => '2026-01-01T00:00:00Z',
    ]);

    expect($driver->parseWebhookRequest($request)[0]->type)->toBe(CampaignEventType::Bounced);
});

it('parses a spam complaint webhook as Complained', function () {
    $driver = new PostmarkDriver(makePostmarkProvider());
    $request = postmarkWebhookRequest([
        'RecordType' => 'SpamComplaint',
        'MessageID' => 'pm-msg-1',
    ]);

    expect($driver->parseWebhookRequest($request)[0]->type)->toBe(CampaignEventType::Complained);
});

it('parses an open and a click webhook', function () {
    $driver = new PostmarkDriver(makePostmarkProvider());

    $open = $driver->parseWebhookRequest(postmarkWebhookRequest([
        'RecordType' => 'Open', 'MessageID' => 'pm-1', 'ReceivedAt' => '2026-01-01T00:00:00Z',
    ]));
    expect($open[0]->type)->toBe(CampaignEventType::Opened);

    $click = $driver->parseWebhookRequest(postmarkWebhookRequest([
        'RecordType' => 'Click', 'MessageID' => 'pm-1', 'ReceivedAt' => '2026-01-01T00:00:00Z', 'OriginalLink' => 'https://acme.com/offer',
    ]));
    expect($click[0]->type)->toBe(CampaignEventType::Clicked);
    expect($click[0]->clickedUrl)->toBe('https://acme.com/offer');
});

it('parses a suppressing subscription change as Unsubscribed, and a non-suppressing one as nothing', function () {
    $driver = new PostmarkDriver(makePostmarkProvider());

    $unsubscribed = $driver->parseWebhookRequest(postmarkWebhookRequest([
        'RecordType' => 'SubscriptionChange', 'MessageID' => 'pm-1', 'SuppressSending' => true,
    ]));
    expect($unsubscribed[0]->type)->toBe(CampaignEventType::Unsubscribed);

    $resubscribed = $driver->parseWebhookRequest(postmarkWebhookRequest([
        'RecordType' => 'SubscriptionChange', 'MessageID' => 'pm-1', 'SuppressSending' => false,
    ]));
    expect($resubscribed)->toBe([]);
});
