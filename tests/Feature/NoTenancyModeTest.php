<?php

use DanPascu\FilamentEmailCampaigns\Enums\SubscriptionStatus;
use DanPascu\FilamentEmailCampaigns\Filament\Widgets\CampaignsOverviewWidget;
use DanPascu\FilamentEmailCampaigns\Models\Campaign;
use DanPascu\FilamentEmailCampaigns\Models\EmailList;
use DanPascu\FilamentEmailCampaigns\Models\Scopes\TenantScope;
use DanPascu\FilamentEmailCampaigns\Models\Subscriber;
use DanPascu\FilamentEmailCampaigns\Support\TenantResolver;

it('isolates tenants from each other once a resolver is configured', function () {
    TenantResolver::resolveUsing(fn () => 1);
    $ownList = EmailList::create(['name' => 'Tenant 1 list']);
    Subscriber::create(['email' => 'one@acme.com']);

    TenantResolver::resolveUsing(fn () => 2);
    EmailList::create(['name' => 'Tenant 2 list']);
    Subscriber::create(['email' => 'two@acme.com']);

    TenantResolver::resolveUsing(fn () => 1);

    expect(EmailList::count())->toBe(1);
    expect(EmailList::first()->id)->toBe($ownList->id);
    expect(Subscriber::count())->toBe(1);
    expect(Subscriber::first()->email)->toBe('one@acme.com');
});

it('auto-fills the tenant foreign key on create when a resolver is configured', function () {
    TenantResolver::resolveUsing(fn () => 7);

    $list = EmailList::create(['name' => 'Auto-filled']);

    expect($list->tenant_id)->toBe(7);
});

it('does not override an explicitly provided tenant foreign key', function () {
    TenantResolver::resolveUsing(fn () => 7);

    $list = EmailList::create(['tenant_id' => 3, 'name' => 'Explicit']);

    expect($list->tenant_id)->toBe(3);
});

it('does not filter at all when the resolver reports no tenant', function () {
    TenantResolver::resolveUsing(fn () => 1);
    EmailList::create(['name' => 'Tenant 1 list']);

    TenantResolver::resolveUsing(fn () => 2);
    EmailList::create(['name' => 'Tenant 2 list']);

    TenantResolver::resolveUsing(fn () => null);

    expect(EmailList::count())->toBe(2);
});

it('sees across every tenant inside TenantScope::withoutScoping regardless of the resolver', function () {
    TenantResolver::resolveUsing(fn () => 1);
    EmailList::create(['name' => 'Tenant 1 list']);

    TenantResolver::resolveUsing(fn () => 2);
    EmailList::create(['name' => 'Tenant 2 list']);

    expect(EmailList::count())->toBe(1);

    $allCount = TenantScope::withoutScoping(fn () => EmailList::count());

    expect($allCount)->toBe(2);
    // The scope is back on as soon as the callback returns.
    expect(EmailList::count())->toBe(1);
});

it('counts only the current tenant in the overview widget', function () {
    TenantResolver::resolveUsing(fn () => 1);
    $list = EmailList::create(['name' => 'Tenant 1 list']);
    $subscriber = Subscriber::create(['email' => 'one@acme.com']);
    $list->subscribers()->attach($subscriber, ['status' => SubscriptionStatus::Subscribed->value]);
    Campaign::create(['email_list_id' => $list->id, 'name' => 'Sent one', 'status' => 'sent']);

    TenantResolver::resolveUsing(fn () => 2);
    $otherList = EmailList::create(['name' => 'Tenant 2 list']);
    $otherSubscriber = Subscriber::create(['email' => 'two@acme.com']);
    $otherList->subscribers()->attach($otherSubscriber, ['status' => SubscriptionStatus::Subscribed->value]);
    Campaign::create(['email_list_id' => $otherList->id, 'name' => 'Sent two', 'status' => 'sent']);
    Campaign::create(['email_list_id' => $otherList->id, 'name' => 'Sent three', 'status' => 'sent']);

    TenantResolver::resolveUsing(fn () => 1);

    $widget = new CampaignsOverviewWidget;
    $stats = invadeGetStats($widget);

    expect($stats[0]->getValue())->toBe(1); // lists
    expect($stats[1]->getValue())->toBe(1); // subscribed contacts
    expect($stats[2]->getValue())->toBe(1); // campaigns sent
});

function invadeGetStats(CampaignsOverviewWidget $widget): array
{
    $method = new ReflectionMethod($widget, 'getStats');
    $method->setAccessible(true);

    return $method->invoke($widget);
}
