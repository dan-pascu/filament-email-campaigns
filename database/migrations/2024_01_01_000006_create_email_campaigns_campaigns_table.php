<?php

use DanPascu\FilamentEmailCampaigns\Database\Migrations\Concerns\HasTenantForeignKey;
use DanPascu\FilamentEmailCampaigns\Enums\CampaignStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use HasTenantForeignKey;

    public function up(): void
    {
        Schema::create('email_campaigns_campaigns', function (Blueprint $table): void {
            $table->id();
            $this->tenantForeignKey($table);
            $table->foreignId('email_list_id')->constrained('email_campaigns_email_lists')->cascadeOnDelete();
            $table->foreignId('segment_id')->nullable()->constrained('email_campaigns_segments')->nullOnDelete();
            $table->foreignId('email_provider_id')->nullable()->constrained('email_campaigns_email_providers')->nullOnDelete();
            $table->string('name');
            $table->string('subject')->nullable();
            $table->string('from_name')->nullable();
            $table->string('from_email')->nullable();
            $table->string('reply_to_email')->nullable();
            $table->longText('html_content')->nullable();
            $table->string('status')->default(CampaignStatus::Draft->value);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->boolean('is_ab_test')->default(false);
            $table->unsignedTinyInteger('ab_test_sample_percentage')->nullable();
            $table->string('ab_test_winning_metric')->nullable();
            $table->timestamp('ab_test_decided_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_campaigns_campaigns');
    }
};
