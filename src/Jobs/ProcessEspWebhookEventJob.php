<?php

namespace DanPascu\FilamentEmailCampaigns\Jobs;

use DanPascu\FilamentEmailCampaigns\Enums\CampaignEventType;
use DanPascu\FilamentEmailCampaigns\Enums\SubscriptionStatus;
use DanPascu\FilamentEmailCampaigns\Models\CampaignSend;
use DanPascu\FilamentEmailCampaigns\Models\Link;
use DanPascu\FilamentEmailCampaigns\Models\Scopes\TenantScope;
use DanPascu\FilamentEmailCampaigns\Models\SubscriberList;
use DanPascu\FilamentEmailCampaigns\Models\SuppressedEmail;
use DanPascu\FilamentEmailCampaigns\Support\EspWebhookEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessEspWebhookEventJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public EspWebhookEvent $event) {}

    public function handle(): void
    {
        TenantScope::withoutScoping(function (): void {
            $send = $this->resolveSend();

            if (! $send) {
                return;
            }

            $link = $this->event->clickedUrl
                ? Link::query()->where('campaign_id', $send->campaign_id)->where('url', $this->event->clickedUrl)->first()
                : null;

            $send->events()->create([
                'tenant_id' => $send->tenant_id,
                'campaign_id' => $send->campaign_id,
                'link_id' => $link?->id,
                'type' => $this->event->type->value,
                'meta' => $this->event->meta,
                'occurred_at' => $this->event->occurredAt,
            ]);

            match ($this->event->type) {
                CampaignEventType::Bounced, CampaignEventType::Complained => $this->suppress($send, $this->event->type),
                CampaignEventType::Unsubscribed => $this->unsubscribe($send),
                default => null,
            };
        });
    }

    protected function resolveSend(): ?CampaignSend
    {
        $sendId = $this->event->meta['send_id'] ?? null;

        if ($sendId && $send = CampaignSend::find($sendId)) {
            return $send;
        }

        if ($this->event->messageId) {
            return CampaignSend::where('esp_message_id', $this->event->messageId)->first();
        }

        return null;
    }

    protected function suppress(CampaignSend $send, CampaignEventType $type): void
    {
        $subscriber = $send->subscriber;

        SuppressedEmail::firstOrCreate(
            ['tenant_id' => $send->tenant_id, 'email' => $subscriber->email],
            ['reason' => $type->value],
        );

        $this->setSubscriptionStatus($send, match ($type) {
            CampaignEventType::Bounced => SubscriptionStatus::Bounced,
            default => SubscriptionStatus::Complained,
        });
    }

    protected function unsubscribe(CampaignSend $send): void
    {
        $this->setSubscriptionStatus($send, SubscriptionStatus::Unsubscribed, unsubscribedNow: true);
    }

    protected function setSubscriptionStatus(CampaignSend $send, SubscriptionStatus $status, bool $unsubscribedNow = false): void
    {
        // Loaded and saved as a model (rather than a bulk query update) so
        // SubscriberList::booted() fires and enrolls the subscriber into any
        // automation triggered by this status change.
        $pivot = SubscriberList::query()
            ->where('subscriber_id', $send->subscriber_id)
            ->where('email_list_id', $send->campaign->email_list_id)
            ->first();

        $pivot?->update(array_filter([
            'status' => $status->value,
            'unsubscribed_at' => $unsubscribedNow ? now() : null,
        ]));
    }
}
