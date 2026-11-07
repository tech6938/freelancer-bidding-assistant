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
            $table->foreignId('bank_card_id')->nullable()->after('payment_method')->constrained('bank_cards')->nullOnDelete();
            $table->string('screenshot_path')->nullable()->after('bank_card_id');
            $table->string('sender_name')->nullable()->after('screenshot_path');
            $table->text('sender_note')->nullable()->after('sender_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['bank_card_id']);
            $table->dropColumn([
                'bank_card_id',
                'screenshot_path',
                'sender_name',
                'sender_note',
            ]);
        });
    }
};
