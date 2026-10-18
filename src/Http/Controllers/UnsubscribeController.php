<?php

namespace DanPascu\FilamentEmailCampaigns\Http\Controllers;

use DanPascu\FilamentEmailCampaigns\Enums\SubscriptionStatus;
use DanPascu\FilamentEmailCampaigns\Models\EmailList;
use DanPascu\FilamentEmailCampaigns\Models\Scopes\TenantScope;
use DanPascu\FilamentEmailCampaigns\Models\Subscriber;
use DanPascu\FilamentEmailCampaigns\Models\SubscriberList;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

class UnsubscribeController extends Controller
{
    public function __invoke(int $list, int $subscriber): View
    {
        // An anonymous request (no auth, no session tenant), so this
        // resolves both rows by their unique ids rather than depending on
        // whatever TenantResolver::current() would otherwise report.
        [$list, $subscriber] = TenantScope::withoutScoping(function () use ($list, $subscriber): array {
            $list = EmailList::findOrFail($list);
            $subscriber = Subscriber::findOrFail($subscriber);

            // Loaded and saved as a model (rather than a bulk query update)
            // so SubscriberList::booted() fires and enrolls the subscriber
            // into any automation triggered by unsubscribing.
            $pivot = SubscriberList::query()
                ->where('email_list_id', $list->id)
                ->where('subscriber_id', $subscriber->id)
                ->first();

            $pivot?->update([
                'status' => SubscriptionStatus::Unsubscribed->value,
                'unsubscribed_at' => now(),
            ]);

            return [$list, $subscriber];
        });

        return view('filament-email-campaigns::unsubscribed', [
            'list' => $list,
            'subscriber' => $subscriber,
        ]);
    }
}
