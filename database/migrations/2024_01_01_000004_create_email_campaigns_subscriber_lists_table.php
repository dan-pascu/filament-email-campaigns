<?php

use DanPascu\FilamentEmailCampaigns\Enums\SubscriptionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_campaigns_subscriber_lists', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('subscriber_id')->constrained('email_campaigns_subscribers')->cascadeOnDelete();
            $table->foreignId('email_list_id')->constrained('email_campaigns_email_lists')->cascadeOnDelete();
            $table->string('status')->default(SubscriptionStatus::Unconfirmed->value);
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();

            $table->unique(['subscriber_id', 'email_list_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_campaigns_subscriber_lists');
    }
};
