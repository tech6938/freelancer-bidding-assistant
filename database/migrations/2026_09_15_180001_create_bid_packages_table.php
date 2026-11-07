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
        Schema::create('bid_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('bid_quantity')->default(50); // strictly bid count, not credits
            $table->decimal('price', 10, 2)->default(0.00);
            $table->string('badge')->nullable(); // e.g. "Popular", "Best Value"
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bid_packages');
    }
};
