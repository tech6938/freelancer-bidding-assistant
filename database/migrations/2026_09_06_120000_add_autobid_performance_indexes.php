<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add performance indexes for auto-bidding hot queries.
     */
    public function up(): void
    {
        // Composite index for daily quota check: WHERE user_id = ? AND created_at >= today
        Schema::table('placed_bids', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'placed_bids_user_date_idx');
        });

        // Composite index for scanning active autobid users
        Schema::table('ai_bid_settings', function (Blueprint $table) {
            $table->index(['is_autobid_enabled', 'status'], 'ai_bid_settings_active_scan_idx');
        });

        // Composite index for template queries by user + active status
        Schema::table('bid_templates', function (Blueprint $table) {
            $table->index(['user_id', 'is_active'], 'bid_templates_user_active_idx');
        });

        // Composite index for skill lookup during matching
        Schema::table('skills', function (Blueprint $table) {
            $table->index(['user_id', 'is_active', 'freelancer_job_id'], 'skills_user_active_job_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('placed_bids', function (Blueprint $table) {
            $table->dropIndex('placed_bids_user_date_idx');
        });

        Schema::table('ai_bid_settings', function (Blueprint $table) {
            $table->dropIndex('ai_bid_settings_active_scan_idx');
        });

        Schema::table('bid_templates', function (Blueprint $table) {
            $table->dropIndex('bid_templates_user_active_idx');
        });

        Schema::table('skills', function (Blueprint $table) {
            $table->dropIndex('skills_user_active_job_idx');
        });
    }
};
