<?php

namespace DanPascu\FilamentEmailCampaigns\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Every top-level model in this package belongs to a tenant (the model configured
 * as the Filament panel's tenant, e.g. a Team). The relationship is always
 * named `tenant()` so Filament's automatic tenant scoping (applied to every
 * Resource by Filament\Resources\Resource\Concerns\BelongsToTenant while
 * inside the panel) works out of the box, as long as the panel is
 * registered with:
 *
 *   ->tenant(Team::class, ownershipRelationship: 'tenant')
 *
 * Outside of Filament (queue jobs, console commands, webhook controllers)
 * that automatic scoping does not apply, so code running there must filter
 * by `tenant_id` explicitly.
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
}
