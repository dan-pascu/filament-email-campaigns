<?php

use DanPascu\FilamentEmailCampaigns\Database\Migrations\Concerns\HasTenantForeignKey;
use DanPascu\FilamentEmailCampaigns\Enums\EspProvider;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use HasTenantForeignKey;

    public function up(): void
    {
        Schema::create('email_campaigns_email_providers', function (Blueprint $table): void {
            $table->id();
            $this->tenantForeignKey($table);
            $table->string('name');
            $table->string('provider')->default(EspProvider::Mailgun->value);
            $table->text('credentials')->nullable();
            $table->text('webhook_signing_secret')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_campaigns_email_providers');
    }
};
