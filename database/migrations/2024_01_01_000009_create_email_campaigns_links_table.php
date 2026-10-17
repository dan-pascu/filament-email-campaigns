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
        Schema::create('email_campaigns_links', function (Blueprint $table): void {
            $table->id();
            $this->tenantForeignKey($table);
            $table->foreignId('campaign_id')->constrained('email_campaigns_campaigns')->cascadeOnDelete();
            $table->text('url');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_campaigns_links');
    }
};
