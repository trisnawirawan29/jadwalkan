<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('business_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['parent_id', 'is_active']);
        });

        $now = now();
        foreach (['Olahraga', 'Karaoke', 'Studio Musik'] as $sortOrder => $name) {
            DB::table('business_categories')->insert([
                'name' => $name,
                'slug' => str($name)->slug(),
                'is_active' => true,
                'sort_order' => $sortOrder,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $sportId = DB::table('business_categories')->where('slug', 'olahraga')->value('id');
        foreach (['Bulutangkis', 'Basket', 'Futsal', 'Tenis'] as $sortOrder => $name) {
            DB::table('business_categories')->insert([
                'parent_id' => $sportId,
                'name' => $name,
                'slug' => str($name)->slug(),
                'is_active' => true,
                'sort_order' => $sortOrder,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('business_categories');
    }
};
