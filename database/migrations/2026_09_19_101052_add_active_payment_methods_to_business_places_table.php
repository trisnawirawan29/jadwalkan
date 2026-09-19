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
        Schema::table('business_places', function (Blueprint $table): void {
            $table->boolean('bank_payment_enabled')->default(true)->after('qris_image');
            $table->boolean('qris_payment_enabled')->default(true)->after('bank_payment_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_places', function (Blueprint $table): void {
            $table->dropColumn(['bank_payment_enabled', 'qris_payment_enabled']);
        });
    }
};
