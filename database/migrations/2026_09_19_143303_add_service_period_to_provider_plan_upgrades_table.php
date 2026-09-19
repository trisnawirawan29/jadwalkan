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
        Schema::table('provider_plan_upgrades', function (Blueprint $table) {
            $table->timestamp('service_started_at')->nullable()->after('amount');
            $table->timestamp('service_expires_at')->nullable()->after('service_started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('provider_plan_upgrades', function (Blueprint $table) {
            $table->dropColumn(['service_started_at', 'service_expires_at']);
        });
    }
};
