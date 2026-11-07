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
        Schema::table('freelancer_connections', function (Blueprint $table) {
            // Prevent the same Freelancer account from being linked to multiple users
            $table->unique('freelancer_user_id', 'freelancer_connections_freelancer_user_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('freelancer_connections', function (Blueprint $table) {
            $table->dropUnique('freelancer_connections_freelancer_user_id_unique');
        });
    }
};
