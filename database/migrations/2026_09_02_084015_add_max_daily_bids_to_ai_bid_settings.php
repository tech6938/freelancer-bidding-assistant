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
        Schema::table('ai_bid_settings', function (Blueprint $table) {
            $table->integer('max_daily_bids')->default(20)->after('is_autobid_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_bid_settings', function (Blueprint $table) {
            //
        });
    }
};
