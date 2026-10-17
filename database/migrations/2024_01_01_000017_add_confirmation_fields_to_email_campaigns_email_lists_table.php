<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_campaigns_email_lists', function (Blueprint $table): void {
            $table->string('confirmation_subject')->nullable()->after('requires_confirmation');
            $table->text('confirmation_body')->nullable()->after('confirmation_subject');
        });
    }

    public function down(): void
    {
        Schema::table('email_campaigns_email_lists', function (Blueprint $table): void {
            $table->dropColumn(['confirmation_subject', 'confirmation_body']);
        });
    }
};
