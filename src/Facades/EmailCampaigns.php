<?php

namespace DanPascu\FilamentEmailCampaigns\Facades;

use DanPascu\FilamentEmailCampaigns\EmailCampaignsManager;
use DanPascu\FilamentEmailCampaigns\Enums\SubscriptionStatus;
use DanPascu\FilamentEmailCampaigns\Models\EmailList;
use DanPascu\FilamentEmailCampaigns\Models\Subscriber;
use DanPascu\FilamentEmailCampaigns\Support\SubscriberData;
use Illuminate\Support\Facades\Facade;

/**
 * @method static ?Subscriber subscribe(EmailList $list, SubscriberData $data, SubscriptionStatus $status = SubscriptionStatus::Subscribed, array $tags = [])
 * @method static bool unsubscribe(EmailList $list, string $email)
 *
 * @see EmailCampaignsManager
 */
class EmailCampaigns extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return EmailCampaignsManager::class;
    }
}
