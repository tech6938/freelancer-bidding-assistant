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
        Schema::create('bank_cards', function (Blueprint $table) {
            $table->id();
            $table->string('bank_name'); // e.g. Meezan Bank, HBL, Visa/Mastercard Debit
            $table->string('account_title'); // Account / Card holder name
            $table->string('card_number'); // Account number or Card number
            $table->string('iban')->nullable();
            $table->string('branch_code')->nullable();
            $table->text('instructions')->nullable(); // e.g. Payment transfer guide
            $table->string('qr_code_image')->nullable(); // Optional QR code or logo image
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_cards');
    }
};
