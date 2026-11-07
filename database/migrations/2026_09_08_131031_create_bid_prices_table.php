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
        Schema::create('bid_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            $table->enum('project_type', ['fixed', 'hourly'])->default('fixed');
            $table->string('currency_code', 10)->default('USD');
            
            $table->unsignedBigInteger('budget_tier_id');
            $table->string('tier_name')->nullable();
            $table->decimal('min_budget', 10, 2)->default(0);
            $table->decimal('max_budget', 10, 2)->nullable();
            
            $table->string('rule_type')->default('custom');
            $table->decimal('bid_amount', 10, 2);
            $table->timestamps();
            $table->unique(['user_id', 'project_type', 'currency_code', 'budget_tier_id'], 'bp_user_tier_unique');
            $table->index(['project_type', 'min_budget', 'max_budget', 'budget_tier_id'], 'bp_tier_lookup_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bid_prices');
    }
};
