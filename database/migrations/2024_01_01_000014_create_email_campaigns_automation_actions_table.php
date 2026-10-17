<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_campaigns_automation_actions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('automation_id')->constrained('email_campaigns_automations')->cascadeOnDelete();
            $table->unsignedInteger('order')->default(0);
            $table->string('type');
            $table->json('config')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_campaigns_automation_actions');
    }
};
