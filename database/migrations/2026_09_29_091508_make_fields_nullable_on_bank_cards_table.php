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
        Schema::table('bank_cards', function (Blueprint $table) {
            $table->string('card_number')->nullable()->change();
            $table->string('logo_image')->nullable()->after('account_title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_cards', function (Blueprint $table) {
            $table->string('card_number')->nullable(false)->change();
            $table->dropColumn(['logo_image']);
        });
    }
};
