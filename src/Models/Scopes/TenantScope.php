<?php

namespace DanPascu\FilamentEmailCampaigns\Models\Scopes;

use Closure;
use DanPascu\FilamentEmailCampaigns\Support\TenantResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Applied to every model using the BelongsToTenant trait. Filters by
 * TenantResolver::current() when it resolves to something, and does
 * nothing when it resolves to null — which is what lets console
 * commands, queue workers and a resolver-driven "super admin" see
 * across tenants.
 */
class TenantScope implements Scope
{
    protected static int $disabledDepth = 0;

    public function apply(Builder $builder, Model $model): void
    {
        if (static::$disabledDepth > 0) {
            return;
        }

        $tenantId = app(TenantResolver::class)->current();

        if ($tenantId === null) {
            return;
        }

        $builder->where(
            $model->qualifyColumn(config('filament-email-campaigns.tenant.foreign_key', 'tenant_id')),
            $tenantId,
        );
    }

    /**
     * Runs a callback with this scope switched off for every model,
     * including on relations lazy-loaded while it runs. For package code
     * that already has an unambiguous id/foreign key to work with (jobs,
     * console commands, webhook and tracking controllers) and must not
     * depend on whatever the current resolver happens to report — those
     * run outside a request/session and typically have no tenant context
     * to resolve at all.
     */
    public static function withoutScoping(Closure $callback): mixed
    {
        static::$disabledDepth++;

        try {
            return $callback();
        } finally {
            static::$disabledDepth--;
        }
    }
}
