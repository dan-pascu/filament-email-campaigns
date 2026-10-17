<?php

namespace DanPascu\FilamentEmailCampaigns\Models;

use DanPascu\FilamentEmailCampaigns\Enums\AutomationTriggerType;
use DanPascu\FilamentEmailCampaigns\Enums\SubscriptionStatus;
use DanPascu\FilamentEmailCampaigns\Services\AutomationEnroller;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class SubscriberList extends Pivot
{
    protected $table = 'email_campaigns_subscriber_lists';

    public $incrementing = true;

    protected $fillable = [
        'subscriber_id',
        'email_list_id',
        'status',
        'subscribed_at',
        'unsubscribed_at',
    ];

    protected $casts = [
        'status' => SubscriptionStatus::class,
        'subscribed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }

    public function emailList(): BelongsTo
    {
        return $this->belongsTo(EmailList::class);
    }

    protected static function booted(): void
    {
        static::saved(function (self $pivot): void {
            if (! $pivot->wasChanged('status')) {
                return;
            }

            $triggerType = match ($pivot->status) {
                SubscriptionStatus::Subscribed => AutomationTriggerType::SubscribesToList,
                SubscriptionStatus::Unsubscribed => AutomationTriggerType::UnsubscribesFromList,
                default => null,
            };

            if ($triggerType === null) {
                return;
            }

            app(AutomationEnroller::class)->enrollForListTrigger(
                $pivot->subscriber,
                $pivot->emailList,
                $triggerType,
            );
        });
    }
}
