<?php

namespace DanPascu\FilamentEmailCampaigns\Jobs;

use DanPascu\FilamentEmailCampaigns\Enums\CampaignStatus;
use DanPascu\FilamentEmailCampaigns\Models\Campaign;
use DanPascu\FilamentEmailCampaigns\Services\AbTestEvaluator;
use DanPascu\FilamentEmailCampaigns\Services\CampaignRecipientResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EvaluateAbTestJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $campaignId) {}

    public function handle(AbTestEvaluator $evaluator, CampaignRecipientResolver $resolver): void
    {
        $campaign = Campaign::find($this->campaignId);

        if (! $campaign || $campaign->status !== CampaignStatus::Sending) {
            return;
        }

        $winner = $evaluator->evaluate($campaign);

        if (! $winner) {
            return;
        }

        foreach ($resolver->resolve($campaign) as $subscriber) {
            SendCampaignToSubscriberJob::dispatch($campaign->id, $winner->id, $subscriber->id);
        }

        $campaign->update(['status' => CampaignStatus::Sent, 'sent_at' => now()]);
    }
}
