<?php

use DanPascu\FilamentEmailCampaigns\Enums\SubscriptionStatus;
use DanPascu\FilamentEmailCampaigns\Jobs\ExportSubscribersJob;
use DanPascu\FilamentEmailCampaigns\Models\EmailList;
use DanPascu\FilamentEmailCampaigns\Models\Subscriber;
use DanPascu\FilamentEmailCampaigns\Tests\Fixtures\NotifiableUser;
use DanPascu\FilamentEmailCampaigns\Tests\Fixtures\Tenant;
use Illuminate\Support\Facades\Storage;

it('writes a CSV of the list\'s subscribers and notifies with a download link', function () {
    Storage::fake('local');

    $tenant = Tenant::create(['name' => 'Acme']);
    $list = EmailList::create(['tenant_id' => $tenant->id, 'name' => 'Newsletter']);
    $user = NotifiableUser::create(['name' => 'Admin']);

    $subscriber = Subscriber::create(['tenant_id' => $tenant->id, 'email' => 'a@acme.com', 'first_name' => 'Ann', 'tags' => ['vip']]);
    $list->subscribers()->attach($subscriber, ['status' => SubscriptionStatus::Subscribed->value]);

    (new ExportSubscribersJob($list->id, $user->id, NotifiableUser::class))->handle();

    $files = Storage::disk('local')->files('email-campaigns-exports');
    expect($files)->toHaveCount(1);

    $contents = Storage::disk('local')->get($files[0]);
    expect($contents)->toContain('a@acme.com');
    expect($contents)->toContain('Ann');
    expect($contents)->toContain('vip');
    expect($contents)->toContain(SubscriptionStatus::Subscribed->value);

    expect($user->notifications()->count())->toBe(1);
});

it('does nothing when the list no longer exists', function () {
    Storage::fake('local');

    (new ExportSubscribersJob(999999))->handle();

    expect(Storage::disk('local')->files('email-campaigns-exports'))->toBe([]);
});
