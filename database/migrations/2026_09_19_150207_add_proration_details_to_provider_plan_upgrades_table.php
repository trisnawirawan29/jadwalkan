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
        Schema::table('provider_plan_upgrades', function (Blueprint $table): void {
            $table->boolean('is_prorated')->default(false)->after('amount');
            $table->unsignedSmallInteger('proration_remaining_days')->nullable()->after('is_prorated');
            $table->unsignedSmallInteger('proration_total_days')->nullable()->after('proration_remaining_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('provider_plan_upgrades', function (Blueprint $table): void {
            $table->dropColumn(['is_prorated', 'proration_remaining_days', 'proration_total_days']);
        });
    }
};
