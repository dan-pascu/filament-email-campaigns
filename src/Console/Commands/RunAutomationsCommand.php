<?php

namespace DanPascu\FilamentEmailCampaigns\Console\Commands;

use DanPascu\FilamentEmailCampaigns\Services\AutomationRunner;
use Illuminate\Console\Command;

class RunAutomationsCommand extends Command
{
    protected $signature = 'email-campaigns:run-automations';

    protected $description = 'Advance every subscriber due for their next automation step.';

    public function handle(AutomationRunner $runner): void
    {
        $runner->runDue();
    }
}
