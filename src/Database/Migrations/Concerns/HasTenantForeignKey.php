<?php

namespace DanPascu\FilamentEmailCampaigns\Database\Migrations\Concerns;

use Illuminate\Database\Schema\Blueprint;

trait HasTenantForeignKey
{
    protected function tenantForeignKey(Blueprint $table): void
    {
        $foreignKey = config('filament-email-campaigns.tenant.foreign_key', 'tenant_id');
        $tenantModel = config('filament-email-campaigns.tenant.model');

        $column = $table->foreignId($foreignKey);

        if ($tenantModel && class_exists($tenantModel)) {
            // Explicit, short, fixed name: the foreign key column is
            // configurable, so a default (auto-generated) constraint name
            // here could grow past MySQL's 64-character identifier limit.
            $column->constrained(
                (new $tenantModel)->getTable(),
                indexName: 'ec_'.$table->getTable().'_tenant_foreign',
            )->cascadeOnDelete();
        }
    }
}
