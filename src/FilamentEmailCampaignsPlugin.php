<?php

namespace DanPascu\FilamentEmailCampaigns;

use DanPascu\FilamentEmailCampaigns\Filament\Resources\AutomationResource;
use DanPascu\FilamentEmailCampaigns\Filament\Resources\CampaignResource;
use DanPascu\FilamentEmailCampaigns\Filament\Resources\EmailListResource;
use DanPascu\FilamentEmailCampaigns\Filament\Resources\EmailProviderResource;
use DanPascu\FilamentEmailCampaigns\Filament\Resources\SubscriberResource;
use DanPascu\FilamentEmailCampaigns\Filament\Resources\SuppressedEmailResource;
use DanPascu\FilamentEmailCampaigns\Filament\Widgets\CampaignsOverviewWidget;
use DanPascu\FilamentEmailCampaigns\Support\SubscriberSourceRegistry;
use DanPascu\FilamentEmailCampaigns\Support\TenantResolver;
use Filament\Contracts\Plugin;
use Filament\Panel;

class FilamentEmailCampaignsPlugin implements Plugin
{
    protected bool $hasAutomations = true;

    protected bool $hasEmailProviderResource = true;

    protected ?\Closure $tenantResolver = null;

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

    /**
     * Resolves "which tenant is this?" yourself instead of relying on
     * Filament's panel tenancy (->tenant(...)). Use this when your app
     * has its own tenancy (e.g. a `client_id` on the authenticated user)
     * and isn't using Filament's multi-tenant panels at all.
     */
    public function resolveTenantUsing(\Closure $resolver): static
    {
        $this->tenantResolver = $resolver;

        TenantResolver::resolveUsing($resolver);

        return $this;
    }

    public function getTenantResolver(): ?\Closure
    {
        return $this->tenantResolver;
    }

    /**
     * Registers extra places subscribers can be imported from, besides the
     * built-in CSV upload. See Contracts\SubscriberSource.
     *
     * @param  array<string|\DanPascu\FilamentEmailCampaigns\Contracts\SubscriberSource>  $sources
     */
    public function subscriberSources(array $sources): static
    {
        SubscriberSourceRegistry::register($sources);

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
            SuppressedEmailResource::class,
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
