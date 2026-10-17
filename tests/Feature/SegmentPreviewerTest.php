<?php

use DanPascu\FilamentEmailCampaigns\Enums\SubscriptionStatus;
use DanPascu\FilamentEmailCampaigns\Models\EmailList;
use DanPascu\FilamentEmailCampaigns\Models\Subscriber;
use DanPascu\FilamentEmailCampaigns\Services\SegmentPreviewer;
use DanPascu\FilamentEmailCampaigns\Tests\Fixtures\Tenant;

it('previews the count and a sample of matching subscribers without saving a segment', function () {
    $tenant = Tenant::create(['name' => 'Acme']);
    $list = EmailList::create(['tenant_id' => $tenant->id, 'name' => 'Newsletter']);

    foreach (range(1, 12) as $i) {
        $subscriber = Subscriber::create(['tenant_id' => $tenant->id, 'email' => "vip{$i}@acme.com", 'tags' => ['vip']]);
        $list->subscribers()->attach($subscriber, ['status' => SubscriptionStatus::Subscribed->value]);
    }

    $other = Subscriber::create(['tenant_id' => $tenant->id, 'email' => 'nobody@acme.com']);
    $list->subscribers()->attach($other, ['status' => SubscriptionStatus::Subscribed->value]);

    $preview = (new SegmentPreviewer)->preview($list, [
        'match' => 'all',
        'groups' => [['match' => 'all', 'rules' => [
            ['field' => 'tags', 'operator' => 'contains', 'value' => 'vip'],
        ]]],
    ]);

    expect($preview['count'])->toBe(12);
    expect($preview['sample'])->toHaveCount(10);
    expect($preview['sample'])->each(fn ($email) => $email->toEndWith('@acme.com'));
});

it('previews an empty match', function () {
    $tenant = Tenant::create(['name' => 'Acme']);
    $list = EmailList::create(['tenant_id' => $tenant->id, 'name' => 'Newsletter']);
    $subscriber = Subscriber::create(['tenant_id' => $tenant->id, 'email' => 'nobody@acme.com']);
    $list->subscribers()->attach($subscriber, ['status' => SubscriptionStatus::Subscribed->value]);

    $preview = (new SegmentPreviewer)->preview($list, [
        'match' => 'all',
        'groups' => [['match' => 'all', 'rules' => [
            ['field' => 'tags', 'operator' => 'contains', 'value' => 'vip'],
        ]]],
    ]);

    expect($preview['count'])->toBe(0);
    expect($preview['sample'])->toBe([]);
});
