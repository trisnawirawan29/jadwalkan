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
        Schema::table('bookings', function (Blueprint $table): void {
            $table->string('payment_proof')->nullable()->after('paid_at');
            $table->timestamp('payment_submitted_at')->nullable()->after('payment_proof');
            $table->timestamp('verified_at')->nullable()->after('payment_submitted_at');
            $table->text('verification_note')->nullable()->after('verified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn(['payment_proof', 'payment_submitted_at', 'verified_at', 'verification_note']);
        });
    }
};
