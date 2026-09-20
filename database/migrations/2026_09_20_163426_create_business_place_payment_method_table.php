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
        Schema::create('business_place_payment_method', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('business_place_id')->constrained('business_places')->cascadeOnDelete();
            $table->foreignId('provider_payment_method_id')->constrained('provider_payment_methods')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['business_place_id', 'provider_payment_method_id'], 'place_payment_method_unique');
        });

        foreach (DB::table('business_places')->get() as $businessPlace) {
            $methods = DB::table('provider_payment_methods')
                ->where('provider_id', $businessPlace->provider_id)
                ->where('is_active', true)
                ->get();

            foreach ($methods as $method) {
                $enabled = $method->type === 'bank_transfer'
                    ? $businessPlace->bank_payment_enabled
                    : $businessPlace->qris_payment_enabled;

                if ($enabled) {
                    DB::table('business_place_payment_method')->insert([
                        'business_place_id' => $businessPlace->id,
                        'provider_payment_method_id' => $method->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_place_payment_method');
    }
};
