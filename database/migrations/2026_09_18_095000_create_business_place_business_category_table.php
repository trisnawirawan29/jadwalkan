<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_place_business_category', function (Blueprint $table) {
            $table->foreignId('business_place_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_category_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['business_place_id', 'business_category_id'], 'place_category_unique');
        });

        if (Schema::hasColumn('business_places', 'business_category_id')) {
            DB::table('business_places')
                ->whereNotNull('business_category_id')
                ->orderBy('id')
                ->eachById(function (object $place): void {
                    DB::table('business_place_business_category')->insert([
                        'business_place_id' => $place->id,
                        'business_category_id' => $place->business_category_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('business_place_business_category');
    }
};
