<?php

namespace DanPascu\FilamentEmailCampaigns\Models\Concerns;

use DanPascu\FilamentEmailCampaigns\Models\Scopes\TenantScope;
use DanPascu\FilamentEmailCampaigns\Support\TenantResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Every top-level model in this package belongs to a tenant. This trait:
 *
 *   - defines a `tenant()` relationship, so Filament's own tenant scoping
 *     (applied to every Resource by
 *     Filament\Resources\Resource\Concerns\BelongsToTenant while inside the
 *     panel) works out of the box, as long as the panel is registered with:
 *
 *       ->tenant(Team::class, ownershipRelationship: 'tenant')
 *
 *   - registers a TenantScope global scope that filters every query by
 *     TenantResolver::current(), which is either Filament's own tenant
 *     (the above, unchanged) or a resolver configured via
 *     FilamentEmailCampaignsPlugin::resolveTenantUsing() /
 *     `filament-email-campaigns.tenant.resolver` for apps that don't use
 *     Filament's panel tenancy at all. The scope is a no-op whenever the
 *     resolver has nothing to report, which is what keeps every existing
 *     console command, queue job and webhook controller working exactly
 *     as before.
 *
 *   - fills in the tenant foreign key on creation when it isn't already
 *     set, from the same resolver.
 */
trait BelongsToTenant
{
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            config('filament-email-campaigns.tenant.model'),
            config('filament-email-campaigns.tenant.foreign_key', 'tenant_id'),
        );
    }

    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Model $model): void {
            $foreignKey = config('filament-email-campaigns.tenant.foreign_key', 'tenant_id');

            if ($model->getAttribute($foreignKey) !== null) {
                return;
            }

            $tenantId = app(TenantResolver::class)->current();

            if ($tenantId !== null) {
                $model->setAttribute($foreignKey, $tenantId);
            }
        });
    }
}
