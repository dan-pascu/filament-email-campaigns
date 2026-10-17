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
            $column->constrained((new $tenantModel)->getTable())->cascadeOnDelete();
        }
    }
}
