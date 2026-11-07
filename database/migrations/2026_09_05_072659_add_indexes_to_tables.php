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
        // 1. ai_bid_settings
        Schema::table('ai_bid_settings', function (Blueprint $table) {
            $table->index('is_autobid_enabled');
            $table->index('project_type');
            $table->index('require_payment_verified');
        });

        // 2. bid_templates
        Schema::table('bid_templates', function (Blueprint $table) {
            $table->index('is_active');
        });

        // 3. placed_bids
        Schema::table('placed_bids', function (Blueprint $table) {
            $table->index('bid_type');
            $table->index('freelancer_bid_id');
        });

        // 4. skills
        Schema::table('skills', function (Blueprint $table) {
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_bid_settings', function (Blueprint $table) {
            $table->dropIndex(['is_autobid_enabled']);
            $table->dropIndex(['project_type']);
            $table->dropIndex(['require_payment_verified']);
        });

        Schema::table('bid_templates', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
        });

        Schema::table('placed_bids', function (Blueprint $table) {
            $table->dropIndex(['bid_type']);
            $table->dropIndex(['freelancer_bid_id']);
        });

        Schema::table('skills', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
        });
    }
};
