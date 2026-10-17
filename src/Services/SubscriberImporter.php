<?php

namespace DanPascu\FilamentEmailCampaigns\Services;

use DanPascu\FilamentEmailCampaigns\Enums\SubscriptionStatus;
use DanPascu\FilamentEmailCampaigns\Models\EmailList;
use DanPascu\FilamentEmailCampaigns\Models\Subscriber;
use DanPascu\FilamentEmailCampaigns\Models\SubscriberList;
use DanPascu\FilamentEmailCampaigns\Models\SuppressedEmail;
use DanPascu\FilamentEmailCampaigns\Support\ImportResult;
use DanPascu\FilamentEmailCampaigns\Support\SubscriberData;

/**
 * Upserts subscribers (by tenant + email) onto a list from any iterable
 * of SubscriberData, skipping suppressed and previously-unsubscribed
 * addresses. Pivot rows are always saved as model instances (never a
 * bulk attach/sync), so SubscriberList::booted() fires and automations
 * triggered by joining the list run as expected.
 */
class SubscriberImporter
{
    /**
     * @param  iterable<mixed>  $records
     * @param  string[]  $tags  extra tags applied to every imported subscriber, on top of each record's own tags
     */
    public function import(EmailList $list, iterable $records, SubscriptionStatus $status, array $tags = []): ImportResult
    {
        $result = new ImportResult;

        $suppressedEmails = SuppressedEmail::query()
            ->where('tenant_id', $list->tenant_id)
            ->pluck('email')
            ->flip();

        foreach ($records as $data) {
            if (! $data instanceof SubscriberData) {
                $result->invalid++;

                continue;
            }

            $email = $this->normalizeEmail($data->email);

            if ($email === null) {
                $result->invalid++;

                continue;
            }

            if (isset($suppressedEmails[$email])) {
                $result->skippedSuppressed++;

                continue;
            }

            $subscriber = Subscriber::query()
                ->where('tenant_id', $list->tenant_id)
                ->where('email', $email)
                ->first();

            $existingPivot = $subscriber
                ? SubscriberList::query()
                    ->where('subscriber_id', $subscriber->id)
                    ->where('email_list_id', $list->id)
                    ->first()
                : null;

            if ($existingPivot?->status === SubscriptionStatus::Unsubscribed) {
                $result->skippedUnsubscribed++;

                continue;
            }

            $isNewSubscriber = $subscriber === null;
            $subscriber ??= new Subscriber(['tenant_id' => $list->tenant_id, 'email' => $email]);

            if ($data->firstName) {
                $subscriber->first_name = $data->firstName;
            }

            if ($data->lastName) {
                $subscriber->last_name = $data->lastName;
            }

            $subscriber->tags = array_values(array_unique([
                ...($subscriber->tags ?? []),
                ...$data->tags,
                ...$tags,
            ]));

            if ($data->extraAttributes !== []) {
                $subscriber->extra_attributes = array_merge($subscriber->extra_attributes ?? [], $data->extraAttributes);
            }

            $subscriber->save();

            $pivot = $existingPivot ?? new SubscriberList([
                'subscriber_id' => $subscriber->id,
                'email_list_id' => $list->id,
            ]);
            $pivot->status = $status;

            if ($status === SubscriptionStatus::Subscribed && $pivot->subscribed_at === null) {
                $pivot->subscribed_at = now();
            }

            $pivot->save();

            $isNewSubscriber ? $result->created++ : $result->updated++;
        }

        return $result;
    }

    protected function normalizeEmail(?string $email): ?string
    {
        $email = trim((string) $email);

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return mb_strtolower($email);
    }
}
