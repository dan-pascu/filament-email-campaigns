<?php

namespace DanPascu\FilamentEmailCampaigns\Services;

use DanPascu\FilamentEmailCampaigns\Models\Campaign;
use DanPascu\FilamentEmailCampaigns\Models\CampaignSend;
use DanPascu\FilamentEmailCampaigns\Models\Subscriber;
use DanPascu\FilamentEmailCampaigns\Models\SuppressedEmail;
use Illuminate\Support\LazyCollection;

class CampaignRecipientResolver
{
    /**
     * Subscribers on the campaign's list who are still subscribed, not
     * suppressed, not already sent to for this campaign, and (if the
     * campaign has a segment) match its conditions.
     *
     * @return LazyCollection<int, Subscriber>
     */
    public function resolve(Campaign $campaign): LazyCollection
    {
        $suppressedEmails = SuppressedEmail::query()
            ->where('tenant_id', $campaign->tenant_id)
            ->pluck('email');

        $alreadySentSubscriberIds = CampaignSend::query()
            ->where('campaign_id', $campaign->id)
            ->pluck('subscriber_id');

        $query = $campaign->emailList->subscribedSubscribers()
            ->whereNotIn('email_campaigns_subscribers.id', $alreadySentSubscriberIds)
            ->whereNotIn('email_campaigns_subscribers.email', $suppressedEmails);

        $segment = $campaign->segment;

        return $query->cursor()
            ->filter(fn (Subscriber $subscriber) => $segment === null || $segment->matches($subscriber));
    }
}
