<?php

use DanPascu\FilamentEmailCampaigns\Enums\SubscriptionStatus;
use DanPascu\FilamentEmailCampaigns\Facades\EmailCampaigns;
use DanPascu\FilamentEmailCampaigns\Models\EmailList;
use DanPascu\FilamentEmailCampaigns\Models\SubscriberList;
use DanPascu\FilamentEmailCampaigns\Models\SuppressedEmail;
use DanPascu\FilamentEmailCampaigns\Support\SubscriberData;
use DanPascu\FilamentEmailCampaigns\Tests\Fixtures\Tenant;

it('subscribes someone to a list programmatically', function () {
    $tenant = Tenant::create(['name' => 'Acme']);
    $list = EmailList::create(['tenant_id' => $tenant->id, 'name' => 'Newsletter']);

    $subscriber = EmailCampaigns::subscribe($list, new SubscriberData(email: 'buyer@acme.com', firstName: 'Buyer'));

    expect($subscriber)->not->toBeNull();
    expect($subscriber->email)->toBe('buyer@acme.com');

    $pivot = SubscriberList::where('subscriber_id', $subscriber->id)->where('email_list_id', $list->id)->first();
    expect($pivot->status)->toBe(SubscriptionStatus::Subscribed);
});

it('does not subscribe a suppressed email', function () {
    $tenant = Tenant::create(['name' => 'Acme']);
    $list = EmailList::create(['tenant_id' => $tenant->id, 'name' => 'Newsletter']);
    SuppressedEmail::create(['tenant_id' => $tenant->id, 'email' => 'blocked@acme.com', 'reason' => 'bounced']);

    $subscriber = EmailCampaigns::subscribe($list, new SubscriberData(email: 'blocked@acme.com'));

    expect($subscriber)->toBeNull();
});

it('unsubscribes someone from a list programmatically', function () {
    $tenant = Tenant::create(['name' => 'Acme']);
    $list = EmailList::create(['tenant_id' => $tenant->id, 'name' => 'Newsletter']);

    $subscriber = EmailCampaigns::subscribe($list, new SubscriberData(email: 'leaving@acme.com'));

    $result = EmailCampaigns::unsubscribe($list, 'leaving@acme.com');

    expect($result)->toBeTrue();

    $pivot = SubscriberList::where('subscriber_id', $subscriber->id)->where('email_list_id', $list->id)->first();
    expect($pivot->status)->toBe(SubscriptionStatus::Unsubscribed);
});

it('returns false unsubscribing someone who was never on the list', function () {
    $tenant = Tenant::create(['name' => 'Acme']);
    $list = EmailList::create(['tenant_id' => $tenant->id, 'name' => 'Newsletter']);

    expect(EmailCampaigns::unsubscribe($list, 'nobody@acme.com'))->toBeFalse();
});
