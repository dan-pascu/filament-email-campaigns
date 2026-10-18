<?php

namespace DanPascu\FilamentEmailCampaigns\Console\Commands;

use DanPascu\FilamentEmailCampaigns\Models\Scopes\TenantScope;
use DanPascu\FilamentEmailCampaigns\Services\AutomationRunner;
use Illuminate\Console\Command;

class RunAutomationsCommand extends Command
{
    protected $signature = 'email-campaigns:run-automations';

    protected $description = 'Advance every subscriber due for their next automation step.';

    public function handle(AutomationRunner $runner): void
    {
        // Advances automations across every tenant, so it must not be
        // scoped to whatever TenantResolver::current() happens to report
        // for a console command (usually nothing).
        TenantScope::withoutScoping(fn () => $runner->runDue());
    }
}
