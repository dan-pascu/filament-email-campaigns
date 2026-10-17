<?php

use DanPascu\FilamentEmailCampaigns\Enums\AutomationActionType;
use DanPascu\FilamentEmailCampaigns\Enums\AutomationTriggerType;
use DanPascu\FilamentEmailCampaigns\Enums\CampaignEventType;
use DanPascu\FilamentEmailCampaigns\Enums\SubscriptionStatus;
use DanPascu\FilamentEmailCampaigns\Jobs\ProcessEspWebhookEventJob;
use DanPascu\FilamentEmailCampaigns\Models\Automation;
use DanPascu\FilamentEmailCampaigns\Models\AutomationActionRun;
use DanPascu\FilamentEmailCampaigns\Models\Campaign;
use DanPascu\FilamentEmailCampaigns\Models\EmailList;
use DanPascu\FilamentEmailCampaigns\Models\Subscriber;
use DanPascu\FilamentEmailCampaigns\Models\SuppressedEmail;
use DanPascu\FilamentEmailCampaigns\Support\EspWebhookEvent;
use DanPascu\FilamentEmailCampaigns\Tests\Fixtures\Tenant;

it('suppresses the email and marks the subscriber bounced on a bounce event', function () {
    $tenant = Tenant::create(['name' => 'Acme']);
    $list = EmailList::create(['tenant_id' => $tenant->id, 'name' => 'Newsletter']);
    $subscriber = Subscriber::create(['tenant_id' => $tenant->id, 'email' => 'bounce@acme.com']);
    $list->subscribers()->attach($subscriber, ['status' => SubscriptionStatus::Subscribed->value]);

    $campaign = Campaign::create(['tenant_id' => $tenant->id, 'email_list_id' => $list->id, 'name' => 'Campaign']);
    $send = $campaign->sends()->create(['tenant_id' => $tenant->id, 'subscriber_id' => $subscriber->id, 'sent_at' => now()]);

    (new ProcessEspWebhookEventJob(new EspWebhookEvent(
        type: CampaignEventType::Bounced,
        messageId: null,
        occurredAt: now(),
        meta: ['send_id' => (string) $send->id],
    )))->handle();

    expect(SuppressedEmail::where('email', 'bounce@acme.com')->exists())->toBeTrue();

    $pivot = $list->subscribers()->wherePivot('subscriber_id', $subscriber->id)->first()->pivot;
    expect($pivot->status)->toBe(SubscriptionStatus::Bounced);
});

it('enrolls the subscriber into a matching automation when they unsubscribe', function () {
    $tenant = Tenant::create(['name' => 'Acme']);
    $list = EmailList::create(['tenant_id' => $tenant->id, 'name' => 'Newsletter']);
    $subscriber = Subscriber::create(['tenant_id' => $tenant->id, 'email' => 'leaving@acme.com']);
    $list->subscribers()->attach($subscriber, ['status' => SubscriptionStatus::Subscribed->value]);

    $automation = Automation::create(['tenant_id' => $tenant->id, 'email_list_id' => $list->id, 'name' => 'Win-back']);
    $automation->triggers()->create(['type' => AutomationTriggerType::UnsubscribesFromList->value]);
    $automation->actions()->create(['order' => 0, 'type' => AutomationActionType::AddTag->value, 'config' => ['tag' => 'churned']]);

    $campaign = Campaign::create(['tenant_id' => $tenant->id, 'email_list_id' => $list->id, 'name' => 'Campaign']);
    $send = $campaign->sends()->create(['tenant_id' => $tenant->id, 'subscriber_id' => $subscriber->id, 'sent_at' => now()]);

    (new ProcessEspWebhookEventJob(new EspWebhookEvent(
        type: CampaignEventType::Unsubscribed,
        messageId: null,
        occurredAt: now(),
        meta: ['send_id' => (string) $send->id],
    )))->handle();

    expect(AutomationActionRun::where('automation_id', $automation->id)->where('subscriber_id', $subscriber->id)->exists())->toBeTrue();
});
