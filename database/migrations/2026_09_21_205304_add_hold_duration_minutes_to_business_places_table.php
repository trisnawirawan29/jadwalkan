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
            $table->unsignedSmallInteger('hold_duration_minutes')->default(10)->after('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_places', function (Blueprint $table): void {
            $table->dropColumn('hold_duration_minutes');
        });
    }
};
