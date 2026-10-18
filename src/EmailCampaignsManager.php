<?php

namespace DanPascu\FilamentEmailCampaigns;

use DanPascu\FilamentEmailCampaigns\Enums\SubscriptionStatus;
use DanPascu\FilamentEmailCampaigns\Models\EmailList;
use DanPascu\FilamentEmailCampaigns\Models\Scopes\TenantScope;
use DanPascu\FilamentEmailCampaigns\Models\Subscriber;
use DanPascu\FilamentEmailCampaigns\Models\SubscriberList;
use DanPascu\FilamentEmailCampaigns\Services\SubscriberImporter;
use DanPascu\FilamentEmailCampaigns\Support\SubscriberData;

/**
 * Programmatic entry point for host applications: subscribe/unsubscribe a
 * single address without going through the Filament panel or a queued
 * import. Accessed via the EmailCampaigns facade.
 */
class EmailCampaignsManager
{
    public function __construct(protected SubscriberImporter $importer) {}

    /**
     * @param  string[]  $tags
     */
    public function subscribe(
        EmailList $list,
        SubscriberData $data,
        SubscriptionStatus $status = SubscriptionStatus::Subscribed,
        array $tags = [],
    ): ?Subscriber {
        return TenantScope::withoutScoping(function () use ($list, $data, $status, $tags): ?Subscriber {
            $this->importer->import($list, [$data], $status, $tags);

            return Subscriber::query()
                ->where('tenant_id', $list->tenant_id)
                ->where('email', mb_strtolower(trim($data->email)))
                ->first();
        });
    }

    public function unsubscribe(EmailList $list, string $email): bool
    {
        return TenantScope::withoutScoping(function () use ($list, $email): bool {
            $email = mb_strtolower(trim($email));

            $subscriber = Subscriber::query()
                ->where('tenant_id', $list->tenant_id)
                ->where('email', $email)
                ->first();

            if (! $subscriber) {
                return false;
            }

            $pivot = SubscriberList::query()
                ->where('subscriber_id', $subscriber->id)
                ->where('email_list_id', $list->id)
                ->first();

            if (! $pivot) {
                return false;
            }

            $pivot->status = SubscriptionStatus::Unsubscribed;
            $pivot->unsubscribed_at = now();
            $pivot->save();

            return true;
        });
    }
}
