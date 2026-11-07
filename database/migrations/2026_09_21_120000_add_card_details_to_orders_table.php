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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('card_holder_name')->nullable()->after('payment_method');
            $table->string('card_brand')->nullable()->after('card_holder_name');
            $table->string('card_last_four', 4)->nullable()->after('card_brand');
            $table->string('card_exp_month', 2)->nullable()->after('card_last_four');
            $table->string('card_exp_year', 4)->nullable()->after('card_exp_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'card_holder_name',
                'card_brand',
                'card_last_four',
                'card_exp_month',
                'card_exp_year',
            ]);
        });
    }
};
