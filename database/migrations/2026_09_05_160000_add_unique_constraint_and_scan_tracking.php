<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Unique constraint to prevent duplicate bids at DB level
        Schema::table('placed_bids', function (Blueprint $table) {
            $table->unique(['user_id', 'freelancer_project_id'], 'placed_bids_user_project_unique');
        });

        // 2. Add scan tracking columns to ai_bid_settings
        Schema::table('ai_bid_settings', function (Blueprint $table) {
            $table->string('status', 20)->default('idle')->after('is_autobid_enabled')
                  ->comment('idle, scanning, paused, quota_reached');
            $table->timestamp('last_scanned_at')->nullable()->after('excluded_keywords');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('placed_bids', function (Blueprint $table) {
            $table->dropUnique('placed_bids_user_project_unique');
        });

        Schema::table('ai_bid_settings', function (Blueprint $table) {
            $table->dropColumn(['status', 'last_scanned_at']);
        });
    }
};
