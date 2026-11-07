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
        Schema::table('placed_bids', function (Blueprint $table) {
            $table->enum('bid_type', ['manual','auto'])
            ->default('manual')
            ->after('freelancer_bid_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('placed_bids', function (Blueprint $table) {
            $table->dropColumn('bid_type');
        });
    }
};
