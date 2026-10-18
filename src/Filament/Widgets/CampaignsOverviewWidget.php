<?php

namespace DanPascu\FilamentEmailCampaigns\Filament\Widgets;

use DanPascu\FilamentEmailCampaigns\Enums\CampaignStatus;
use DanPascu\FilamentEmailCampaigns\Enums\SubscriptionStatus;
use DanPascu\FilamentEmailCampaigns\Models\Campaign;
use DanPascu\FilamentEmailCampaigns\Models\EmailList;
use DanPascu\FilamentEmailCampaigns\Models\Subscriber;
use DanPascu\FilamentEmailCampaigns\Support\TenantResolver;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CampaignsOverviewWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $tenantId = app(TenantResolver::class)->current();

        return [
            Stat::make(
                __('filament-email-campaigns::translations.widgets.overview.lists'),
                EmailList::query()->where('tenant_id', $tenantId)->count(),
            ),
            Stat::make(
                __('filament-email-campaigns::translations.widgets.overview.subscribed_contacts'),
                Subscriber::query()
                    ->where('tenant_id', $tenantId)
                    ->whereHas('lists', fn ($query) => $query->where('status', SubscriptionStatus::Subscribed->value))
                    ->count(),
            ),
            Stat::make(
                __('filament-email-campaigns::translations.widgets.overview.campaigns_sent'),
                Campaign::query()
                    ->where('tenant_id', $tenantId)
                    ->where('status', CampaignStatus::Sent->value)
                    ->count(),
            ),
        ];
    }
}
