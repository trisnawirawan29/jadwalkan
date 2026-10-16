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
        Schema::table('sport_groups', function (Blueprint $table): void {
            $table->string('level', 20)->default('beginner')->after('business_category_id');
            $table->index(['business_category_id', 'level', 'activity_date', 'status'], 'sport_groups_matching_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sport_groups', function (Blueprint $table): void {
            $table->dropIndex('sport_groups_matching_idx');
            $table->dropColumn('level');
        });
    }
};
