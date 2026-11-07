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
        Schema::create('placed_bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->unsignedBigInteger('freelancer_project_id')->index();
        $table->decimal('bid_amount', 10, 2);
        $table->integer('delivery_days')->default(7);
        $table->text('proposal_text')->nullable();
        $table->unsignedBigInteger('freelancer_bid_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('placed_bids');
    }
};
