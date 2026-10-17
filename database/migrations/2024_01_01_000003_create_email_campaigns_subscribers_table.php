<?php

use DanPascu\FilamentEmailCampaigns\Database\Migrations\Concerns\HasTenantForeignKey;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use HasTenantForeignKey;

    public function up(): void
    {
        Schema::create('email_campaigns_subscribers', function (Blueprint $table): void {
            $table->id();
            $this->tenantForeignKey($table);
            $table->string('email');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->json('tags')->nullable();
            $table->json('extra_attributes')->nullable();
            $table->timestamps();

            $table->unique([config('filament-email-campaigns.tenant.foreign_key', 'tenant_id'), 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_campaigns_subscribers');
    }
};
