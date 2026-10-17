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
        Schema::create('email_campaigns_email_lists', function (Blueprint $table): void {
            $table->id();
            $this->tenantForeignKey($table);
            $table->foreignId('email_provider_id')->nullable()->constrained('email_campaigns_email_providers')->nullOnDelete();
            $table->string('name');
            $table->string('default_from_name')->nullable();
            $table->string('default_from_email')->nullable();
            $table->string('default_reply_to_email')->nullable();
            $table->boolean('requires_confirmation')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_campaigns_email_lists');
    }
};
