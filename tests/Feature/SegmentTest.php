<?php

use DanPascu\FilamentEmailCampaigns\Enums\SubscriptionStatus;
use DanPascu\FilamentEmailCampaigns\Models\EmailList;
use DanPascu\FilamentEmailCampaigns\Models\Segment;
use DanPascu\FilamentEmailCampaigns\Models\Subscriber;
use DanPascu\FilamentEmailCampaigns\Tests\Fixtures\Tenant;

it('matches using the legacy flat conditions shape', function () {
    $tenant = Tenant::create(['name' => 'Acme']);
    $list = EmailList::create(['tenant_id' => $tenant->id, 'name' => 'Newsletter']);
    $subscriber = Subscriber::create(['tenant_id' => $tenant->id, 'email' => 'vip@acme.com', 'tags' => ['vip']]);

    $segment = new Segment(['email_list_id' => $list->id, 'conditions' => [
        'match' => 'all',
        'rules' => [['field' => 'tags', 'operator' => 'contains', 'value' => 'vip']],
    ]]);

    expect($segment->matches($subscriber))->toBeTrue();
});

it('combines groups with OR and rules within a group with AND', function () {
    $tenant = Tenant::create(['name' => 'Acme']);
    $list = EmailList::create(['tenant_id' => $tenant->id, 'name' => 'Newsletter']);

    $vipAtAcme = Subscriber::create(['tenant_id' => $tenant->id, 'email' => 'vip@acme.com', 'tags' => ['vip']]);
    $vipElsewhere = Subscriber::create(['tenant_id' => $tenant->id, 'email' => 'vip@other.com', 'tags' => ['vip']]);
    $leadAtAcme = Subscriber::create(['tenant_id' => $tenant->id, 'email' => 'lead@acme.com', 'tags' => ['lead']]);
    $neither = Subscriber::create(['tenant_id' => $tenant->id, 'email' => 'nobody@other.com']);

    $segment = new Segment(['email_list_id' => $list->id, 'conditions' => [
        'match' => 'any',
        'groups' => [
            ['match' => 'all', 'rules' => [
                ['field' => 'tags', 'operator' => 'contains', 'value' => 'vip'],
                ['field' => 'email', 'operator' => 'ends_with', 'value' => '@acme.com'],
            ]],
            ['match' => 'all', 'rules' => [
                ['field' => 'tags', 'operator' => 'contains', 'value' => 'lead'],
            ]],
        ],
    ]]);

    expect($segment->matches($vipAtAcme))->toBeTrue();
    expect($segment->matches($leadAtAcme))->toBeTrue();
    expect($segment->matches($vipElsewhere))->toBeFalse();
    expect($segment->matches($neither))->toBeFalse();
});

it('matches on subscribed_at relative to the segment\'s own list', function () {
    $tenant = Tenant::create(['name' => 'Acme']);
    $list = EmailList::create(['tenant_id' => $tenant->id, 'name' => 'Newsletter']);

    $early = Subscriber::create(['tenant_id' => $tenant->id, 'email' => 'early@acme.com']);
    $list->subscribers()->attach($early, ['status' => SubscriptionStatus::Subscribed->value, 'subscribed_at' => '2025-01-01']);

    $late = Subscriber::create(['tenant_id' => $tenant->id, 'email' => 'late@acme.com']);
    $list->subscribers()->attach($late, ['status' => SubscriptionStatus::Subscribed->value, 'subscribed_at' => '2026-06-01']);

    $segment = new Segment(['email_list_id' => $list->id, 'conditions' => [
        'match' => 'all',
        'groups' => [['match' => 'all', 'rules' => [
            ['field' => 'subscribed_at', 'operator' => 'after', 'value' => '2026-01-01'],
        ]]],
    ]]);

    expect($segment->matches($early->fresh()))->toBeFalse();
    expect($segment->matches($late->fresh()))->toBeTrue();
});

it('matches on an extra_attributes path', function () {
    $tenant = Tenant::create(['name' => 'Acme']);
    $list = EmailList::create(['tenant_id' => $tenant->id, 'name' => 'Newsletter']);
    $subscriber = Subscriber::create(['tenant_id' => $tenant->id, 'email' => 'c@acme.com', 'extra_attributes' => ['company' => 'Claar']]);

    $segment = new Segment(['email_list_id' => $list->id, 'conditions' => [
        'match' => 'all',
        'groups' => [['match' => 'all', 'rules' => [
            ['field' => 'extra_attributes.company', 'operator' => 'equals', 'value' => 'Claar'],
        ]]],
    ]]);

    expect($segment->matches($subscriber))->toBeTrue();
});

it('matches everyone when there are no groups or rules', function () {
    $tenant = Tenant::create(['name' => 'Acme']);
    $list = EmailList::create(['tenant_id' => $tenant->id, 'name' => 'Newsletter']);
    $subscriber = Subscriber::create(['tenant_id' => $tenant->id, 'email' => 'anyone@acme.com']);

    $segment = new Segment(['email_list_id' => $list->id, 'conditions' => ['match' => 'all', 'groups' => []]]);

    expect($segment->matches($subscriber))->toBeTrue();
});
