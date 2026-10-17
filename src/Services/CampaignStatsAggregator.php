<?php

namespace DanPascu\FilamentEmailCampaigns\Services;

use DanPascu\FilamentEmailCampaigns\Enums\CampaignEventType;
use DanPascu\FilamentEmailCampaigns\Models\Campaign;

class CampaignStatsAggregator
{
    /**
     * @return array{sent: int, delivered: int, opened: int, clicked: int, bounced: int, complained: int, unsubscribed: int, open_rate: float, click_rate: float, bounce_rate: float}
     */
    public function summarize(Campaign $campaign): array
    {
        $sent = $campaign->sends()->whereNotNull('sent_at')->count();
        $delivered = $campaign->uniqueEventCount(CampaignEventType::Delivered);
        $opened = $campaign->uniqueEventCount(CampaignEventType::Opened);
        $clicked = $campaign->uniqueEventCount(CampaignEventType::Clicked);
        $bounced = $campaign->uniqueEventCount(CampaignEventType::Bounced);
        $complained = $campaign->uniqueEventCount(CampaignEventType::Complained);
        $unsubscribed = $campaign->uniqueEventCount(CampaignEventType::Unsubscribed);

        return [
            'sent' => $sent,
            'delivered' => $delivered,
            'opened' => $opened,
            'clicked' => $clicked,
            'bounced' => $bounced,
            'complained' => $complained,
            'unsubscribed' => $unsubscribed,
            'open_rate' => $this->rate($opened, $sent),
            'click_rate' => $this->rate($clicked, $sent),
            'bounce_rate' => $this->rate($bounced, $sent),
        ];
    }

    protected function rate(int $count, int $of): float
    {
        return $of > 0 ? round($count / $of * 100, 1) : 0.0;
    }
}
