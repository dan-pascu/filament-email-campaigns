<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_campaigns_automation_action_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('automation_id')->constrained('email_campaigns_automations')->cascadeOnDelete();
            $table->foreignId('automation_action_id')->constrained('email_campaigns_automation_actions')->cascadeOnDelete();
            $table->foreignId('subscriber_id')->constrained('email_campaigns_subscribers')->cascadeOnDelete();
            $table->timestamp('run_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['run_at', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_campaigns_automation_action_runs');
    }
};
