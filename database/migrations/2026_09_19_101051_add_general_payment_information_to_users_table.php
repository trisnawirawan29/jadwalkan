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
        Schema::table('users', function (Blueprint $table): void {
            $table->string('payment_bank_name', 100)->nullable()->after('bio');
            $table->string('payment_bank_account_name', 150)->nullable()->after('payment_bank_name');
            $table->string('payment_bank_account_number', 50)->nullable()->after('payment_bank_account_name');
            $table->string('payment_qris_image')->nullable()->after('payment_bank_account_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['payment_bank_name', 'payment_bank_account_name', 'payment_bank_account_number', 'payment_qris_image']);
        });
    }
};
