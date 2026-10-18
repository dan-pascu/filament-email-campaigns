<?php

namespace DanPascu\FilamentEmailCampaigns\Console\Commands;

use DanPascu\FilamentEmailCampaigns\Enums\CampaignStatus;
use DanPascu\FilamentEmailCampaigns\Jobs\SendCampaignJob;
use DanPascu\FilamentEmailCampaigns\Models\Campaign;
use DanPascu\FilamentEmailCampaigns\Models\Scopes\TenantScope;
use Illuminate\Console\Command;

class ScheduleDueCampaignsCommand extends Command
{
    protected $signature = 'email-campaigns:schedule-due-campaigns';

    protected $description = 'Dispatch sending for every campaign whose scheduled time has passed.';

    public function handle(): void
    {
        // This intentionally scans every tenant, so it must not be scoped
        // to whatever TenantResolver::current() happens to report for a
        // console command (usually nothing).
        TenantScope::withoutScoping(function (): void {
            Campaign::query()
                ->where('status', CampaignStatus::Scheduled->value)
                ->where('scheduled_at', '<=', now())
                ->each(fn (Campaign $campaign) => SendCampaignJob::dispatch($campaign->id));
        });
    }
}
