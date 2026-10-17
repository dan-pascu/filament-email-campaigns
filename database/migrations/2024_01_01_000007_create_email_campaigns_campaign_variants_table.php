<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_campaigns_campaign_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campaign_id')->constrained('email_campaigns_campaigns')->cascadeOnDelete();
            $table->string('name');
            $table->string('subject')->nullable();
            $table->longText('html_content')->nullable();
            $table->boolean('is_winner')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_campaigns_campaign_variants');
    }
};
