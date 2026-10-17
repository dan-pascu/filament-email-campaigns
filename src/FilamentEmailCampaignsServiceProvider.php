<?php

namespace DanPascu\FilamentEmailCampaigns;

use DanPascu\FilamentEmailCampaigns\Console\Commands\RunAutomationsCommand;
use DanPascu\FilamentEmailCampaigns\Console\Commands\ScheduleDueCampaignsCommand;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentEmailCampaignsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-email-campaigns')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->discoversMigrations()
            ->hasRoute('filament-email-campaigns')
            ->hasCommands([
                ScheduleDueCampaignsCommand::class,
                RunAutomationsCommand::class,
            ]);
    }

    public function packageBooted(): void
    {
        RateLimiter::for('email-campaigns-sends', fn () => Limit::perSecond(
            max(1, (int) config('filament-email-campaigns.sending.rate_limit_per_second', 10)),
        ));

        $this->app->booted(function (): void {
            $schedule = $this->app->make(Schedule::class);

            $schedule->command(ScheduleDueCampaignsCommand::class)->everyMinute();
            $schedule->command(RunAutomationsCommand::class)->everyMinute();
        });
    }
}
