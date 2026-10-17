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
            // Explicit, short name: the default one (table + column +
            // "foreign") exceeds MySQL's 64-character identifier limit.
            $table->foreignId('automation_action_id')->constrained(
                'email_campaigns_automation_actions',
                indexName: 'ec_automation_action_runs_action_id_foreign',
            )->cascadeOnDelete();
            $table->foreignId('subscriber_id')->constrained('email_campaigns_subscribers')->cascadeOnDelete();
            $table->timestamp('run_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Explicit, short name: the default one is exactly 64
            // characters, with no margin under MySQL's limit.
            $table->index(['run_at', 'completed_at'], 'ec_automation_action_runs_run_completed_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_campaigns_automation_action_runs');
    }
};
