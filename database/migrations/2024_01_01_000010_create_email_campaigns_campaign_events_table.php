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
        Schema::create('email_campaigns_campaign_events', function (Blueprint $table): void {
            $table->id();
            $this->tenantForeignKey($table);
            $table->foreignId('campaign_id')->constrained('email_campaigns_campaigns')->cascadeOnDelete();
            $table->foreignId('campaign_send_id')->constrained('email_campaigns_campaign_sends')->cascadeOnDelete();
            $table->foreignId('link_id')->nullable()->constrained('email_campaigns_links')->cascadeOnDelete();
            $table->string('type');
            $table->json('meta')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['campaign_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_campaigns_campaign_events');
    }
};
