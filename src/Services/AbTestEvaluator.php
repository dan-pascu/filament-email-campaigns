<?php

namespace DanPascu\FilamentEmailCampaigns\Services;

use DanPascu\FilamentEmailCampaigns\Enums\CampaignEventType;
use DanPascu\FilamentEmailCampaigns\Models\Campaign;
use DanPascu\FilamentEmailCampaigns\Models\CampaignEvent;
use DanPascu\FilamentEmailCampaigns\Models\CampaignVariant;

class AbTestEvaluator
{
    /**
     * Picks the variant with the highest unique open/click rate among the
     * sample that has already been sent, marks it as the winner, and
     * returns it so the rest of the list can be sent that variant.
     */
    public function evaluate(Campaign $campaign): ?CampaignVariant
    {
        $variants = $campaign->variants;

        if ($variants->count() < 2) {
            return null;
        }

        $metric = $campaign->ab_test_winning_metric === 'clicks'
            ? CampaignEventType::Clicked
            : CampaignEventType::Opened;

        $winner = $variants
            ->sortByDesc(fn (CampaignVariant $variant) => $this->metricCountFor($variant, $metric))
            ->first();

        $winner->update(['is_winner' => true]);
        $campaign->update(['ab_test_decided_at' => now()]);

        return $winner;
    }

    protected function metricCountFor(CampaignVariant $variant, CampaignEventType $metric): int
    {
        $sendIds = $variant->sends()->pluck('id');

        if ($sendIds->isEmpty()) {
            return 0;
        }

        return CampaignEvent::query()
            ->whereIn('campaign_send_id', $sendIds)
            ->where('type', $metric->value)
            ->distinct('campaign_send_id')
            ->count('campaign_send_id');
    }
}
