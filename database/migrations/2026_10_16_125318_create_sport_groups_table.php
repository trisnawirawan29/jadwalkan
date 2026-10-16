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
        Schema::create('sport_groups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('business_category_id')->constrained('business_categories')->cascadeOnDelete();
            $table->foreignId('selected_business_service_id')->nullable()->constrained('business_services')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('activity_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedTinyInteger('max_members')->default(4);
            $table->string('status')->default('open')->index();
            $table->timestamps();

            $table->index(['business_category_id', 'activity_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sport_groups');
    }
};
