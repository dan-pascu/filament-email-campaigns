<?php

use DanPascu\FilamentEmailCampaigns\Enums\AutomationActionType;
use DanPascu\FilamentEmailCampaigns\Enums\AutomationTriggerType;
use DanPascu\FilamentEmailCampaigns\Enums\SubscriptionStatus;
use DanPascu\FilamentEmailCampaigns\Models\Automation;
use DanPascu\FilamentEmailCampaigns\Models\AutomationActionRun;
use DanPascu\FilamentEmailCampaigns\Models\EmailList;
use DanPascu\FilamentEmailCampaigns\Models\Subscriber;
use DanPascu\FilamentEmailCampaigns\Models\SubscriberList;
use DanPascu\FilamentEmailCampaigns\Models\SuppressedEmail;
use DanPascu\FilamentEmailCampaigns\Services\SubscriberImporter;
use DanPascu\FilamentEmailCampaigns\Support\SubscriberData;
use DanPascu\FilamentEmailCampaigns\Tests\Fixtures\Tenant;

function makeEmailList(?Tenant $tenant = null): EmailList
{
    $tenant ??= Tenant::create(['name' => 'Acme']);

    return EmailList::create(['tenant_id' => $tenant->id, 'name' => 'Newsletter']);
}

it('creates a new subscriber and subscribes them to the list', function () {
    $list = makeEmailList();

    $result = (new SubscriberImporter)->import($list, [
        new SubscriberData(email: 'Ann@Acme.com', firstName: 'Ann', tags: ['vip']),
    ], SubscriptionStatus::Subscribed);

    expect($result->created)->toBe(1);
    expect($result->updated)->toBe(0);

    $subscriber = Subscriber::where('tenant_id', $list->tenant_id)->where('email', 'ann@acme.com')->first();
    expect($subscriber)->not->toBeNull();
    expect($subscriber->first_name)->toBe('Ann');
    expect($subscriber->tags)->toBe(['vip']);

    $pivot = SubscriberList::where('subscriber_id', $subscriber->id)->where('email_list_id', $list->id)->first();
    expect($pivot->status)->toBe(SubscriptionStatus::Subscribed);
    expect($pivot->subscribed_at)->not->toBeNull();
});

it('updates an existing subscriber and merges tags', function () {
    $list = makeEmailList();
    $subscriber = Subscriber::create(['tenant_id' => $list->tenant_id, 'email' => 'ann@acme.com', 'tags' => ['vip']]);

    $result = (new SubscriberImporter)->import($list, [
        new SubscriberData(email: 'ann@acme.com', lastName: 'Smith', tags: ['lead']),
    ], SubscriptionStatus::Subscribed, tags: ['imported']);

    expect($result->created)->toBe(0);
    expect($result->updated)->toBe(1);

    $subscriber->refresh();
    expect($subscriber->last_name)->toBe('Smith');
    expect($subscriber->tags)->toEqualCanonicalizing(['vip', 'lead', 'imported']);
});

it('skips a suppressed email', function () {
    $list = makeEmailList();
    SuppressedEmail::create(['tenant_id' => $list->tenant_id, 'email' => 'blocked@acme.com', 'reason' => 'bounced']);

    $result = (new SubscriberImporter)->import($list, [
        new SubscriberData(email: 'blocked@acme.com'),
    ], SubscriptionStatus::Subscribed);

    expect($result->skippedSuppressed)->toBe(1);
    expect(Subscriber::where('tenant_id', $list->tenant_id)->where('email', 'blocked@acme.com')->exists())->toBeFalse();
});

it('skips someone who already unsubscribed instead of re-subscribing them', function () {
    $list = makeEmailList();
    $subscriber = Subscriber::create(['tenant_id' => $list->tenant_id, 'email' => 'left@acme.com']);
    SubscriberList::create([
        'subscriber_id' => $subscriber->id,
        'email_list_id' => $list->id,
        'status' => SubscriptionStatus::Unsubscribed->value,
    ]);

    $result = (new SubscriberImporter)->import($list, [
        new SubscriberData(email: 'left@acme.com'),
    ], SubscriptionStatus::Subscribed);

    expect($result->skippedUnsubscribed)->toBe(1);

    $pivot = SubscriberList::where('subscriber_id', $subscriber->id)->where('email_list_id', $list->id)->first();
    expect($pivot->status)->toBe(SubscriptionStatus::Unsubscribed);
});

it('counts an invalid email as invalid', function () {
    $list = makeEmailList();

    $result = (new SubscriberImporter)->import($list, [
        new SubscriberData(email: 'not-an-email'),
    ], SubscriptionStatus::Subscribed);

    expect($result->invalid)->toBe(1);
    expect($result->total())->toBe(1);
});

it('counts a non-SubscriberData record as invalid', function () {
    $list = makeEmailList();

    $result = (new SubscriberImporter)->import($list, ['not a subscriber data object'], SubscriptionStatus::Subscribed);

    expect($result->invalid)->toBe(1);
});

it('triggers automations through the pivot save, not a bulk attach', function () {
    $list = makeEmailList();

    $automation = Automation::create([
        'tenant_id' => $list->tenant_id,
        'email_list_id' => $list->id,
        'name' => 'Welcome',
    ]);
    $automation->triggers()->create(['type' => AutomationTriggerType::SubscribesToList->value]);
    $automation->actions()->create(['order' => 0, 'type' => AutomationActionType::AddTag->value, 'config' => ['tag' => 'welcomed']]);

    (new SubscriberImporter)->import($list, [
        new SubscriberData(email: 'fresh@acme.com'),
    ], SubscriptionStatus::Subscribed);

    $subscriber = Subscriber::where('tenant_id', $list->tenant_id)->where('email', 'fresh@acme.com')->first();

    expect(AutomationActionRun::where('automation_id', $automation->id)->where('subscriber_id', $subscriber->id)->exists())->toBeTrue();
});
