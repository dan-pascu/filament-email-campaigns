<?php

use DanPascu\FilamentEmailCampaigns\Actions\ExportSubscribersActionHandler;
use DanPascu\FilamentEmailCampaigns\Actions\ImportSubscribersActionHandler;
use DanPascu\FilamentEmailCampaigns\Enums\SubscriptionStatus;
use DanPascu\FilamentEmailCampaigns\Jobs\ExportSubscribersJob;
use DanPascu\FilamentEmailCampaigns\Jobs\ImportSubscribersJob;
use DanPascu\FilamentEmailCampaigns\Models\EmailList;
use DanPascu\FilamentEmailCampaigns\Tests\Fixtures\NotifiableUser;
use DanPascu\FilamentEmailCampaigns\Tests\Fixtures\Tenant;
use Illuminate\Support\Facades\Queue;

it('dispatches ImportSubscribersJob with the right arguments', function () {
    Queue::fake();

    $tenant = Tenant::create(['name' => 'Acme']);
    $list = EmailList::create(['tenant_id' => $tenant->id, 'name' => 'Newsletter']);
    $user = NotifiableUser::create(['name' => 'Admin']);

    (new ImportSubscribersActionHandler)(
        list: $list,
        sourceKey: 'csv',
        option: '{"path":"x.csv"}',
        status: SubscriptionStatus::Unconfirmed,
        tags: ['imported'],
        notifiable: $user,
    );

    Queue::assertPushed(ImportSubscribersJob::class, function (ImportSubscribersJob $job) use ($list, $user) {
        return $job->emailListId === $list->id
            && $job->sourceKey === 'csv'
            && $job->option === '{"path":"x.csv"}'
            && $job->status === SubscriptionStatus::Unconfirmed->value
            && $job->tags === ['imported']
            && $job->notifiableId === $user->id
            && $job->notifiableType === NotifiableUser::class;
    });
});

it('dispatches ExportSubscribersJob with the right arguments', function () {
    Queue::fake();

    $tenant = Tenant::create(['name' => 'Acme']);
    $list = EmailList::create(['tenant_id' => $tenant->id, 'name' => 'Newsletter']);
    $user = NotifiableUser::create(['name' => 'Admin']);

    (new ExportSubscribersActionHandler)($list, $user);

    Queue::assertPushed(ExportSubscribersJob::class, function (ExportSubscribersJob $job) use ($list, $user) {
        return $job->emailListId === $list->id
            && $job->notifiableId === $user->id
            && $job->notifiableType === NotifiableUser::class;
    });
});
