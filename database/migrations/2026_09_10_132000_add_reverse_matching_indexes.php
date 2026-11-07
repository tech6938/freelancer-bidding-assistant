<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to add optimized indexes for project-centric reverse matching.
     */
    public function up(): void
    {
        // 1. Composite index on skills for reverse lookup: WHERE freelancer_job_id IN (...) AND is_active = true
        Schema::table('skills', function (Blueprint $table) {
            $table->index(['freelancer_job_id', 'is_active', 'user_id'], 'skills_reverse_match_idx');
        });

        // 2. Composite index on ai_bid_settings for quick reverse qualification
        Schema::table('ai_bid_settings', function (Blueprint $table) {
            $table->index(['is_autobid_enabled', 'min_budget', 'max_budget'], 'ai_bid_settings_reverse_budget_idx');
            $table->index(['user_id', 'is_autobid_enabled'], 'ai_bid_settings_user_enabled_idx');
        });

        // 3. Index on project_filters for fast eager user filter checks
        Schema::table('project_filters', function (Blueprint $table) {
            $table->index('user_id', 'project_filters_user_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skills', function (Blueprint $table) {
            $table->dropIndex('skills_reverse_match_idx');
        });

        Schema::table('ai_bid_settings', function (Blueprint $table) {
            $table->dropIndex('ai_bid_settings_reverse_budget_idx');
            $table->dropIndex('ai_bid_settings_user_enabled_idx');
        });

        Schema::table('project_filters', function (Blueprint $table) {
            $table->dropIndex('project_filters_user_id_idx');
        });
    }
};
