<?php

use DanPascu\FilamentEmailCampaigns\Enums\CampaignEventType;
use DanPascu\FilamentEmailCampaigns\Models\Campaign;
use DanPascu\FilamentEmailCampaigns\Models\EmailList;
use DanPascu\FilamentEmailCampaigns\Models\Subscriber;
use DanPascu\FilamentEmailCampaigns\Services\AbTestEvaluator;
use DanPascu\FilamentEmailCampaigns\Tests\Fixtures\Tenant;

it('picks the variant with the highest unique open count as the winner', function () {
    $tenant = Tenant::create(['name' => 'Acme']);
    $list = EmailList::create(['tenant_id' => $tenant->id, 'name' => 'Newsletter']);

    $campaign = Campaign::create([
        'tenant_id' => $tenant->id,
        'email_list_id' => $list->id,
        'name' => 'A/B campaign',
        'is_ab_test' => true,
        'ab_test_winning_metric' => 'opens',
    ]);

    $variantA = $campaign->variants()->create(['name' => 'A']);
    $variantB = $campaign->variants()->create(['name' => 'B']);

    foreach (range(1, 3) as $i) {
        $subscriber = Subscriber::create(['tenant_id' => $tenant->id, 'email' => "a{$i}@acme.com"]);
        $send = $campaign->sends()->create([
            'tenant_id' => $tenant->id,
            'campaign_variant_id' => $variantA->id,
            'subscriber_id' => $subscriber->id,
            'sent_at' => now(),
        ]);
        $send->events()->create([
            'tenant_id' => $tenant->id,
            'campaign_id' => $campaign->id,
            'type' => CampaignEventType::Opened->value,
            'occurred_at' => now(),
        ]);
    }

    $subscriberB = Subscriber::create(['tenant_id' => $tenant->id, 'email' => 'b1@acme.com']);
    $sendB = $campaign->sends()->create([
        'tenant_id' => $tenant->id,
        'campaign_variant_id' => $variantB->id,
        'subscriber_id' => $subscriberB->id,
        'sent_at' => now(),
    ]);
    $sendB->events()->create([
        'tenant_id' => $tenant->id,
        'campaign_id' => $campaign->id,
        'type' => CampaignEventType::Opened->value,
        'occurred_at' => now(),
    ]);

    $winner = (new AbTestEvaluator)->evaluate($campaign->fresh());

    expect($winner->id)->toBe($variantA->id);
    expect($winner->refresh()->is_winner)->toBeTrue();
    expect($campaign->refresh()->ab_test_decided_at)->not->toBeNull();
});
