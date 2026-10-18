<?php

namespace DanPascu\FilamentEmailCampaigns\Jobs;

use DanPascu\FilamentEmailCampaigns\Enums\CampaignStatus;
use DanPascu\FilamentEmailCampaigns\Models\Campaign;
use DanPascu\FilamentEmailCampaigns\Models\Scopes\TenantScope;
use DanPascu\FilamentEmailCampaigns\Services\CampaignRecipientResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

/**
 * Starts sending a campaign: resolves recipients and dispatches one
 * SendCampaignToSubscriberJob per recipient. For an A/B test, only the
 * sample goes out now; EvaluateAbTestJob (dispatched with a delay) sends
 * the winning variant to everyone else later.
 */
class SendCampaignJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $campaignId)
    {
        $this->onConnection(config('filament-email-campaigns.sending.queue_connection'));
        $this->onQueue(config('filament-email-campaigns.sending.queue', 'email-campaigns'));
    }

    public function handle(CampaignRecipientResolver $resolver): void
    {
        TenantScope::withoutScoping(function () use ($resolver): void {
            $campaign = Campaign::find($this->campaignId);

            if (! $campaign || ! $campaign->isSendable()) {
                return;
            }

            $campaign->update(['status' => CampaignStatus::Sending]);

            $variants = $campaign->variants;
            $recipients = $resolver->resolve($campaign);

            if ($campaign->is_ab_test && $variants->count() > 1) {
                $this->sendAbTestSample($campaign, $variants, $recipients);

                EvaluateAbTestJob::dispatch($campaign->id)
                    ->delay(now()->addMinutes(
                        (int) config('filament-email-campaigns.ab_testing.default_wait_minutes', 240),
                    ));

                return;
            }

            $variantId = $variants->first()?->id;

            foreach ($recipients as $subscriber) {
                SendCampaignToSubscriberJob::dispatch($campaign->id, $variantId, $subscriber->id);
            }

            $campaign->update(['status' => CampaignStatus::Sent, 'sent_at' => now()]);
        });
    }

    protected function sendAbTestSample(Campaign $campaign, Collection $variants, LazyCollection $recipients): void
    {
        $samplePercentage = $campaign->ab_test_sample_percentage ?: 20;
        $variantCount = $variants->count();
        $index = 0;

        foreach ($recipients as $subscriber) {
            $index++;

            // Only `samplePercentage` of the list is used for the test; the
            // rest is left unsent until the winner is picked.
            if (($index % 100) >= $samplePercentage) {
                continue;
            }

            $variant = $variants[$index % $variantCount];

            SendCampaignToSubscriberJob::dispatch($campaign->id, $variant->id, $subscriber->id);
        }
    }
}
