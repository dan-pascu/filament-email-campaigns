<?php

use DanPascu\FilamentEmailCampaigns\Enums\SubscriptionStatus;
use DanPascu\FilamentEmailCampaigns\Jobs\ImportSubscribersJob;
use DanPascu\FilamentEmailCampaigns\Models\EmailList;
use DanPascu\FilamentEmailCampaigns\Models\Subscriber;
use DanPascu\FilamentEmailCampaigns\Support\SubscriberData;
use DanPascu\FilamentEmailCampaigns\Support\SubscriberSourceRegistry;
use DanPascu\FilamentEmailCampaigns\Tests\Fixtures\FixtureSubscriberSource;
use DanPascu\FilamentEmailCampaigns\Tests\Fixtures\NotifiableUser;
use DanPascu\FilamentEmailCampaigns\Tests\Fixtures\Tenant;

it('imports every record from the source and notifies with a summary', function () {
    SubscriberSourceRegistry::register([FixtureSubscriberSource::class]);
    FixtureSubscriberSource::$records = [
        'batch' => [
            new SubscriberData(email: 'one@acme.com'),
            new SubscriberData(email: 'two@acme.com'),
        ],
    ];

    $tenant = Tenant::create(['name' => 'Acme']);
    $list = EmailList::create(['tenant_id' => $tenant->id, 'name' => 'Newsletter']);
    $user = NotifiableUser::create(['name' => 'Admin']);

    (new ImportSubscribersJob($list->id, 'fixture', 'batch', SubscriptionStatus::Subscribed->value, [], $user->id, NotifiableUser::class))
        ->handle(app(DanPascu\FilamentEmailCampaigns\Services\SubscriberImporter::class));

    expect(Subscriber::where('tenant_id', $tenant->id)->count())->toBe(2);
    expect($user->notifications()->count())->toBe(1);
});

it('does nothing when the list no longer exists', function () {
    SubscriberSourceRegistry::register([FixtureSubscriberSource::class]);

    (new ImportSubscribersJob(999999, 'fixture', null, SubscriptionStatus::Subscribed->value))
        ->handle(app(DanPascu\FilamentEmailCampaigns\Services\SubscriberImporter::class));

    expect(Subscriber::count())->toBe(0);
});
