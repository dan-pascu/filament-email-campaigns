<?php

namespace DanPascu\FilamentEmailCampaigns\Services;

use DanPascu\FilamentEmailCampaigns\Jobs\SendConfirmationEmailJob;
use DanPascu\FilamentEmailCampaigns\Models\EmailList;
use DanPascu\FilamentEmailCampaigns\Models\Subscriber;
use DanPascu\FilamentEmailCampaigns\Models\SubscriberList;

/**
 * Sends (or resends) the double opt-in confirmation email for a
 * subscriber/list pair. Dispatches on the queue, same as campaign sends.
 */
class ConfirmationMailer
{
    /**
     * Called from SubscriberList::booted() on every save; only actually
     * sends when the list requires confirmation and the pivot is
     * currently unconfirmed.
     */
    public function sendIfRequired(SubscriberList $pivot): void
    {
        if ($pivot->status->value !== 'unconfirmed') {
            return;
        }

        if (! $pivot->emailList?->requires_confirmation) {
            return;
        }

        SendConfirmationEmailJob::dispatch($pivot->subscriber_id, $pivot->email_list_id);
    }

    /**
     * Resends unconditionally (used by the manual "resend" action), even
     * if the list's requires_confirmation setting has since changed.
     */
    public function send(EmailList $list, Subscriber $subscriber): void
    {
        SendConfirmationEmailJob::dispatch($subscriber->id, $list->id);
    }
}
