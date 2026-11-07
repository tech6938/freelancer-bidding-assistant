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
        Schema::create('project_filters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // API-Based Settings (Stored as JSON)
            $table->json('countries')->nullable();
            $table->json('languages')->nullable();
            $table->json('currencies')->nullable();

            // Budget Filters
            $table->decimal('min_fixed_budget', 10, 2)->default(10.00);
            $table->decimal('max_fixed_budget', 10, 2)->default(3000.00);
            $table->decimal('min_hourly_rate', 10, 2)->default(10.00);
            $table->decimal('max_hourly_rate', 10, 2)->default(100.00);
            $table->enum('project_type', ['both', 'fixed', 'hourly'])->default('both');

            // Client Filters
            $table->decimal('min_client_rating', 3, 1)->default(0.0);
            $table->integer('min_client_reviews')->default(0);
            $table->boolean('require_payment_verified')->default(true);
            $table->boolean('require_identity_verified')->default(false);

            // Excluded Keywords
            $table->json('excluded_keywords')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_filters');
    }
};
