<?php

use DanPascu\FilamentEmailCampaigns\Enums\SubscriptionStatus;
use DanPascu\FilamentEmailCampaigns\Models\Campaign;
use DanPascu\FilamentEmailCampaigns\Models\EmailList;
use DanPascu\FilamentEmailCampaigns\Models\Segment;
use DanPascu\FilamentEmailCampaigns\Models\Subscriber;
use DanPascu\FilamentEmailCampaigns\Models\SuppressedEmail;
use DanPascu\FilamentEmailCampaigns\Services\CampaignRecipientResolver;
use DanPascu\FilamentEmailCampaigns\Tests\Fixtures\Tenant;

it('only resolves subscribed, non-suppressed subscribers matching the segment', function () {
    $tenant = Tenant::create(['name' => 'Acme']);

    $list = EmailList::create(['tenant_id' => $tenant->id, 'name' => 'Newsletter']);

    $subscribed = Subscriber::create(['tenant_id' => $tenant->id, 'email' => 'vip@acme.com', 'tags' => ['vip']]);
    $unsubscribed = Subscriber::create(['tenant_id' => $tenant->id, 'email' => 'gone@acme.com']);
    $suppressed = Subscriber::create(['tenant_id' => $tenant->id, 'email' => 'bounced@acme.com', 'tags' => ['vip']]);
    $wrongSegment = Subscriber::create(['tenant_id' => $tenant->id, 'email' => 'nobody@acme.com']);

    $list->subscribers()->attach($subscribed, ['status' => SubscriptionStatus::Subscribed->value]);
    $list->subscribers()->attach($unsubscribed, ['status' => SubscriptionStatus::Unsubscribed->value]);
    $list->subscribers()->attach($suppressed, ['status' => SubscriptionStatus::Subscribed->value]);
    $list->subscribers()->attach($wrongSegment, ['status' => SubscriptionStatus::Subscribed->value]);

    SuppressedEmail::create(['tenant_id' => $tenant->id, 'email' => 'bounced@acme.com', 'reason' => 'bounced']);

    $segment = Segment::create([
        'tenant_id' => $tenant->id,
        'email_list_id' => $list->id,
        'name' => 'VIPs',
        'conditions' => ['match' => 'all', 'rules' => [
            ['field' => 'tags', 'operator' => 'contains', 'value' => 'vip'],
        ]],
    ]);

    $campaign = Campaign::create([
        'tenant_id' => $tenant->id,
        'email_list_id' => $list->id,
        'segment_id' => $segment->id,
        'name' => 'Test campaign',
    ]);

    $recipients = (new CampaignRecipientResolver)->resolve($campaign)->pluck('email')->all();

    expect($recipients)->toBe(['vip@acme.com']);
});
