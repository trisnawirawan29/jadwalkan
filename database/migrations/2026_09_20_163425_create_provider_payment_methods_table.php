<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('provider_payment_methods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('provider_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('bank_name', 100)->nullable();
            $table->string('account_name', 150)->nullable();
            $table->string('account_number', 50)->nullable();
            $table->string('qris_image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['provider_id', 'type']);
        });

        foreach (DB::table('users')->get() as $user) {
            if ($user->payment_bank_name && $user->payment_bank_account_number) {
                DB::table('provider_payment_methods')->insert([
                    'provider_id' => $user->id,
                    'type' => 'bank_transfer',
                    'bank_name' => $user->payment_bank_name,
                    'account_name' => $user->payment_bank_account_name,
                    'account_number' => $user->payment_bank_account_number,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($user->payment_qris_image) {
                DB::table('provider_payment_methods')->insert([
                    'provider_id' => $user->id,
                    'type' => 'qris',
                    'qris_image' => $user->payment_qris_image,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_payment_methods');
    }
};
