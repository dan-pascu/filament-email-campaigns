<?php

use DanPascu\FilamentEmailCampaigns\Enums\AutomationActionType;
use DanPascu\FilamentEmailCampaigns\Enums\AutomationTriggerType;
use DanPascu\FilamentEmailCampaigns\Enums\EspProvider;
use DanPascu\FilamentEmailCampaigns\Enums\SubscriptionStatus;
use DanPascu\FilamentEmailCampaigns\Jobs\SendConfirmationEmailJob;
use DanPascu\FilamentEmailCampaigns\Models\Automation;
use DanPascu\FilamentEmailCampaigns\Models\AutomationActionRun;
use DanPascu\FilamentEmailCampaigns\Models\EmailList;
use DanPascu\FilamentEmailCampaigns\Models\EmailProvider;
use DanPascu\FilamentEmailCampaigns\Models\Subscriber;
use DanPascu\FilamentEmailCampaigns\Models\SubscriberList;
use DanPascu\FilamentEmailCampaigns\Models\TransactionalMail;
use DanPascu\FilamentEmailCampaigns\Services\ConfirmationMailer;
use DanPascu\FilamentEmailCampaigns\Tests\Fixtures\FakeEspDriver;
use DanPascu\FilamentEmailCampaigns\Tests\Fixtures\Tenant;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;

function makeConfirmableList(bool $requiresConfirmation = true): array
{
    config(['filament-email-campaigns.providers.mailgun' => FakeEspDriver::class]);

    $tenant = Tenant::create(['name' => 'Acme']);
    $provider = EmailProvider::create([
        'tenant_id' => $tenant->id,
        'name' => 'Mailgun',
        'provider' => EspProvider::Mailgun->value,
        'credentials' => ['domain' => 'mail.acme.com', 'api_key' => 'key', 'region' => 'us'],
    ]);
    $list = EmailList::create([
        'tenant_id' => $tenant->id,
        'name' => 'Newsletter',
        'email_provider_id' => $provider->id,
        'default_from_name' => 'Acme',
        'default_from_email' => 'hello@acme.com',
        'requires_confirmation' => $requiresConfirmation,
    ]);

    return [$tenant, $list];
}

it('dispatches a confirmation email when a subscriber joins a list that requires it', function () {
    Queue::fake();
    [, $list] = makeConfirmableList();

    $subscriber = Subscriber::create(['tenant_id' => $list->tenant_id, 'email' => 'new@acme.com']);
    SubscriberList::create([
        'subscriber_id' => $subscriber->id,
        'email_list_id' => $list->id,
        'status' => SubscriptionStatus::Unconfirmed->value,
    ]);

    Queue::assertPushed(SendConfirmationEmailJob::class, fn (SendConfirmationEmailJob $job) => $job->subscriberId === $subscriber->id
        && $job->emailListId === $list->id);
});

it('does not dispatch a confirmation email when the list does not require it', function () {
    Queue::fake();
    [, $list] = makeConfirmableList(requiresConfirmation: false);

    $subscriber = Subscriber::create(['tenant_id' => $list->tenant_id, 'email' => 'new@acme.com']);
    SubscriberList::create([
        'subscriber_id' => $subscriber->id,
        'email_list_id' => $list->id,
        'status' => SubscriptionStatus::Unconfirmed->value,
    ]);

    Queue::assertNotPushed(SendConfirmationEmailJob::class);
});

it('sends the confirmation email with a signed link and records a transactional mail', function () {
    [, $list] = makeConfirmableList();
    $subscriber = Subscriber::create(['tenant_id' => $list->tenant_id, 'email' => 'new@acme.com']);

    (new SendConfirmationEmailJob($subscriber->id, $list->id))->handle();

    expect(FakeEspDriver::$sent)->toHaveCount(1);
    $message = FakeEspDriver::$sent[0];
    expect($message->toEmail)->toBe('new@acme.com');
    expect($message->html)->toContain('/email-campaigns/confirm/'.$list->id.'/'.$subscriber->id);

    expect(TransactionalMail::where('tenant_id', $list->tenant_id)->where('to', 'new@acme.com')->exists())->toBeTrue();
});

it('uses the list\'s custom confirmation subject and body, substituting the placeholder', function () {
    [, $list] = makeConfirmableList();
    $list->update([
        'confirmation_subject' => 'Confirm you, :list style',
        'confirmation_body' => 'Click here: {confirmation_url} thanks',
    ]);
    $subscriber = Subscriber::create(['tenant_id' => $list->tenant_id, 'email' => 'custom@acme.com']);

    (new SendConfirmationEmailJob($subscriber->id, $list->id))->handle();

    $message = FakeEspDriver::$sent[0];
    expect($message->subject)->toBe('Confirm you, :list style');
    expect($message->html)->toStartWith('Click here: ');
    expect($message->html)->toContain('/email-campaigns/confirm/');
});

it('resends the confirmation email unconditionally via the manual action', function () {
    Queue::fake();
    [, $list] = makeConfirmableList(requiresConfirmation: false);
    $subscriber = Subscriber::create(['tenant_id' => $list->tenant_id, 'email' => 'resend@acme.com']);

    app(ConfirmationMailer::class)->send($list, $subscriber);

    Queue::assertPushed(SendConfirmationEmailJob::class, fn (SendConfirmationEmailJob $job) => $job->subscriberId === $subscriber->id
        && $job->emailListId === $list->id);
});

it('confirms the subscription, sets subscribed_at, and triggers automations', function () {
    [, $list] = makeConfirmableList();
    $subscriber = Subscriber::create(['tenant_id' => $list->tenant_id, 'email' => 'confirm@acme.com']);
    SubscriberList::create([
        'subscriber_id' => $subscriber->id,
        'email_list_id' => $list->id,
        'status' => SubscriptionStatus::Unconfirmed->value,
    ]);

    $automation = Automation::create(['tenant_id' => $list->tenant_id, 'email_list_id' => $list->id, 'name' => 'Welcome']);
    $automation->triggers()->create(['type' => AutomationTriggerType::SubscribesToList->value]);
    $automation->actions()->create(['order' => 0, 'type' => AutomationActionType::AddTag->value, 'config' => ['tag' => 'confirmed']]);

    $url = URL::signedRoute('filament-email-campaigns.confirm', ['list' => $list->id, 'subscriber' => $subscriber->id]);

    $this->get($url)->assertOk();

    $pivot = SubscriberList::where('subscriber_id', $subscriber->id)->where('email_list_id', $list->id)->first();
    expect($pivot->status)->toBe(SubscriptionStatus::Subscribed);
    expect($pivot->subscribed_at)->not->toBeNull();

    expect(AutomationActionRun::where('automation_id', $automation->id)->where('subscriber_id', $subscriber->id)->exists())->toBeTrue();
});

it('rejects an unsigned confirmation link', function () {
    [, $list] = makeConfirmableList();
    $subscriber = Subscriber::create(['tenant_id' => $list->tenant_id, 'email' => 'nosig@acme.com']);

    $this->get("/email-campaigns/confirm/{$list->id}/{$subscriber->id}")->assertForbidden();
});

it('excludes unconfirmed subscribers from campaign sends', function () {
    [, $list] = makeConfirmableList();

    $confirmed = Subscriber::create(['tenant_id' => $list->tenant_id, 'email' => 'confirmed@acme.com']);
    $unconfirmed = Subscriber::create(['tenant_id' => $list->tenant_id, 'email' => 'unconfirmed@acme.com']);

    $list->subscribers()->attach($confirmed, ['status' => SubscriptionStatus::Subscribed->value]);
    $list->subscribers()->attach($unconfirmed, ['status' => SubscriptionStatus::Unconfirmed->value]);

    $campaign = \DanPascu\FilamentEmailCampaigns\Models\Campaign::create([
        'tenant_id' => $list->tenant_id,
        'email_list_id' => $list->id,
        'name' => 'Test campaign',
    ]);

    $recipients = (new \DanPascu\FilamentEmailCampaigns\Services\CampaignRecipientResolver)->resolve($campaign)->pluck('email')->all();

    expect($recipients)->toBe(['confirmed@acme.com']);
});
