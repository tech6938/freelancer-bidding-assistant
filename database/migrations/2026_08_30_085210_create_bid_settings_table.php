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
        Schema::create('bid_settings', function (Blueprint $table) {
            $table->id();
          $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Project Delivery & Milestone
            $table->integer('default_delivery_days')->default(7);
            $table->integer('milestone_percentage')->default(100);

            // Speed, Anti-Ban & Quota Limits
            $table->integer('bid_delay_seconds')->default(4);
            $table->integer('max_daily_bids')->default(20);
            $table->boolean('auto_retry_failed_bids')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bid_settings');
    }
};
