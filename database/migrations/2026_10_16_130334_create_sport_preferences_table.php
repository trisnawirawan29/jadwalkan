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
        Schema::create('sport_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_category_id')->constrained('business_categories')->cascadeOnDelete();
            $table->string('level', 20);
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();

            $table->unique(['user_id', 'business_category_id', 'day_of_week', 'start_time', 'end_time'], 'sport_preferences_user_sport_slot_unique');
            $table->index(['business_category_id', 'level', 'day_of_week', 'start_time', 'end_time'], 'sport_preferences_matching_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sport_preferences');
    }
};
