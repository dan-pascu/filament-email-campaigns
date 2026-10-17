<?php

namespace DanPascu\FilamentEmailCampaigns\Http\Controllers;

use DanPascu\FilamentEmailCampaigns\Enums\SubscriptionStatus;
use DanPascu\FilamentEmailCampaigns\Models\EmailList;
use DanPascu\FilamentEmailCampaigns\Models\Subscriber;
use DanPascu\FilamentEmailCampaigns\Models\SubscriberList;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

class UnsubscribeController extends Controller
{
    public function __invoke(EmailList $list, Subscriber $subscriber): View
    {
        // Loaded and saved as a model (rather than a bulk query update) so
        // SubscriberList::booted() fires and enrolls the subscriber into any
        // automation triggered by unsubscribing.
        $pivot = SubscriberList::query()
            ->where('email_list_id', $list->id)
            ->where('subscriber_id', $subscriber->id)
            ->first();

        $pivot?->update([
            'status' => SubscriptionStatus::Unsubscribed->value,
            'unsubscribed_at' => now(),
        ]);

        return view('filament-email-campaigns::unsubscribed', [
            'list' => $list,
            'subscriber' => $subscriber,
        ]);
    }
}
