<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add auto-bid tracking columns to placed_bids table.
     *
     * These columns track which template was used, the skill match score,
     * and the reason for auto-bidding — enabling full audit trail in the logs view.
     */
    public function up(): void
    {
        Schema::table('placed_bids', function (Blueprint $table) {
            // Which template was auto-selected (null for manual bids or fallback)
            $table->unsignedBigInteger('matched_template_id')->nullable()->after('bid_type');
            $table->foreign('matched_template_id')
                  ->references('id')
                  ->on('bid_templates')
                  ->nullOnDelete();

            // Jaccard similarity score (0.0 to 1.0) for the template match
            $table->decimal('skill_match_score', 5, 4)->default(0)->after('matched_template_id');

            // Human-readable reason for why this project was selected
            $table->string('auto_bid_reason', 500)->nullable()->after('skill_match_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('placed_bids', function (Blueprint $table) {
            $table->dropForeign(['matched_template_id']);
            $table->dropColumn(['matched_template_id', 'skill_match_score', 'auto_bid_reason']);
        });
    }
};
