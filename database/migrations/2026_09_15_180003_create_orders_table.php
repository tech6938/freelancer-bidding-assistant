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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('order_type'); // 'subscription' or 'bid_package'
            $table->unsignedBigInteger('item_id')->nullable(); // ID of SubscriptionPlan or BidPackage
            $table->string('item_name');
            $table->decimal('amount', 10, 2)->default(0.00);
            $table->integer('bid_quantity_added')->default(0); // AI bids credited to user balance
            $table->string('payment_method')->default('instant_dummy');
            $table->string('status')->default('completed'); // completed, refunded
            $table->string('transaction_ref')->unique();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'order_type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
