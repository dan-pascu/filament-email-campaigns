<?php

namespace DanPascu\FilamentEmailCampaigns;

use DanPascu\FilamentEmailCampaigns\Filament\Resources\AutomationResource;
use DanPascu\FilamentEmailCampaigns\Filament\Resources\CampaignResource;
use DanPascu\FilamentEmailCampaigns\Filament\Resources\EmailListResource;
use DanPascu\FilamentEmailCampaigns\Filament\Resources\EmailProviderResource;
use DanPascu\FilamentEmailCampaigns\Filament\Resources\SubscriberResource;
use DanPascu\FilamentEmailCampaigns\Filament\Widgets\CampaignsOverviewWidget;
use Filament\Contracts\Plugin;
use Filament\Panel;

class FilamentEmailCampaignsPlugin implements Plugin
{
    protected bool $hasAutomations = true;

    protected bool $hasEmailProviderResource = true;

    public function getId(): string
    {
        return 'filament-email-campaigns';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    public function automations(bool $condition = true): static
    {
        $this->hasAutomations = $condition;

        return $this;
    }

    public function emailProviderResource(bool $condition = true): static
    {
        $this->hasEmailProviderResource = $condition;

        return $this;
    }

    public function register(Panel $panel): void
    {
        $resources = [
            EmailListResource::class,
            SubscriberResource::class,
            CampaignResource::class,
        ];

        if ($this->hasEmailProviderResource) {
            $resources[] = EmailProviderResource::class;
        }

        if ($this->hasAutomations) {
            $resources[] = AutomationResource::class;
        }

        $panel
            ->resources($resources)
            ->widgets([
                CampaignsOverviewWidget::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
