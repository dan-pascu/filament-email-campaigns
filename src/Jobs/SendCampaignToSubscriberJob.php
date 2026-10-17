<?php

namespace DanPascu\FilamentEmailCampaigns\Jobs;

use DanPascu\FilamentEmailCampaigns\Enums\CampaignEventType;
use DanPascu\FilamentEmailCampaigns\Models\Campaign;
use DanPascu\FilamentEmailCampaigns\Models\CampaignSend;
use DanPascu\FilamentEmailCampaigns\Models\Subscriber;
use DanPascu\FilamentEmailCampaigns\Services\CampaignContentTracker;
use DanPascu\FilamentEmailCampaigns\Support\EspDriverManager;
use DanPascu\FilamentEmailCampaigns\Support\EspMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;

class SendCampaignToSubscriberJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $campaignId,
        public ?int $campaignVariantId,
        public int $subscriberId,
    ) {
        $this->onConnection(config('filament-email-campaigns.sending.queue_connection'));
        $this->onQueue(config('filament-email-campaigns.sending.queue', 'email-campaigns'));
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new RateLimited('email-campaigns-sends')];
    }

    public function handle(CampaignContentTracker $tracker): void
    {
        $campaign = Campaign::find($this->campaignId);
        $subscriber = Subscriber::find($this->subscriberId);

        if (! $campaign || ! $subscriber) {
            return;
        }

        if (! $campaign->isSendable() && $campaign->status->value !== 'sending') {
            return;
        }

        $emailProvider = $campaign->resolvedEmailProvider();

        if (! $emailProvider) {
            return;
        }

        $send = CampaignSend::firstOrCreate(
            ['campaign_id' => $campaign->id, 'subscriber_id' => $subscriber->id],
            ['tenant_id' => $campaign->tenant_id, 'campaign_variant_id' => $this->campaignVariantId],
        );

        if ($send->sent_at !== null) {
            return;
        }

        $variant = $send->variant;
        $subject = $variant?->subject ?: $campaign->subject;
        $html = $variant?->html_content ?: $campaign->html_content;
        $html = $tracker->track($html, $send);

        $message = new EspMessage(
            toEmail: $subscriber->email,
            toName: $subscriber->fullName(),
            fromEmail: $campaign->from_email ?: $campaign->emailList->default_from_email,
            fromName: $campaign->from_name ?: $campaign->emailList->default_from_name,
            subject: $subject,
            html: $html,
            replyToEmail: $campaign->reply_to_email ?: $campaign->emailList->default_reply_to_email,
            tags: ["campaign-{$campaign->id}"],
            metadata: ['send_id' => (string) $send->id],
        );

        $result = EspDriverManager::driverFor($emailProvider)->send($message);

        if ($result->successful) {
            $send->update(['esp_message_id' => $result->messageId, 'sent_at' => now()]);
            $send->events()->create([
                'tenant_id' => $campaign->tenant_id,
                'campaign_id' => $campaign->id,
                'type' => CampaignEventType::Sent->value,
                'occurred_at' => now(),
            ]);

            return;
        }

        $send->update(['failed_at' => now(), 'failure_reason' => $result->errorMessage]);
        $send->events()->create([
            'tenant_id' => $campaign->tenant_id,
            'campaign_id' => $campaign->id,
            'type' => CampaignEventType::Failed->value,
            'meta' => ['reason' => $result->errorMessage],
            'occurred_at' => now(),
        ]);
    }
}
