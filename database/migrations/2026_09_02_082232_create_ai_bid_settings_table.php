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
        Schema::create('ai_bid_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('is_autobid_enabled')->default(false);
            $table->integer('min_budget')->default(30);
            $table->integer('max_budget')->default(2500);
            $table->string('preferred_currency', 10)->default('USD');
            $table->enum('project_type', ['both', 'fixed', 'hourly'])->default('both');
            $table->enum('bid_calculation_mode', ['minimum', 'average', 'maximum'])->default('average');
            $table->integer('delivery_days')->default(7);
            $table->integer('bidding_delay_seconds')->default(12);
            $table->boolean('require_payment_verified')->default(true);
            $table->decimal('min_client_rating', 2, 1)->default(4.0);
            $table->integer('min_client_reviews')->default(0);
            $table->json('excluded_keywords')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_bid_settings');
    }
};
