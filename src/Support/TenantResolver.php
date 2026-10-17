<?php

namespace DanPascu\FilamentEmailCampaigns\Support;

use Closure;
use Filament\Facades\Filament;
use Throwable;

/**
 * The single source of truth for "which tenant is this?", used by
 * TenantScope and everywhere else the package needs the current tenant.
 *
 * Resolution order:
 *   1. A closure registered via FilamentEmailCampaignsPlugin::resolveTenantUsing().
 *   2. An invokable class configured at `filament-email-campaigns.tenant.resolver`.
 *   3. Filament's own panel tenancy (Filament::getTenant()), which is what
 *      every host app gets by default and keeps working exactly as before.
 *
 * Returning null (no resolver configured and no active Filament tenant,
 * e.g. in a console command or queued job) means "don't scope" rather
 * than "scope to nothing" — see TenantScope.
 */
class TenantResolver
{
    protected static ?Closure $resolver = null;

    public static function resolveUsing(?Closure $resolver): void
    {
        static::$resolver = $resolver;
    }

    public static function getResolver(): ?Closure
    {
        return static::$resolver;
    }

    public function current(): int|string|null
    {
        if (static::$resolver !== null) {
            return (static::$resolver)();
        }

        if ($resolverClass = config('filament-email-campaigns.tenant.resolver')) {
            return app($resolverClass)();
        }

        return $this->resolveFromFilamentTenancy();
    }

    protected function resolveFromFilamentTenancy(): int|string|null
    {
        try {
            return Filament::getTenant()?->getKey();
        } catch (Throwable) {
            return null;
        }
    }
}
