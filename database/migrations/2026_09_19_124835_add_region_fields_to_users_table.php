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
        Schema::table('users', function (Blueprint $table): void {
            $table->string('province_code', 10)->nullable()->after('location');
            $table->string('province_name')->nullable()->after('province_code');
            $table->string('regency_code', 15)->nullable()->after('province_name');
            $table->string('regency_name')->nullable()->after('regency_code');
            $table->string('district_code', 20)->nullable()->after('regency_name');
            $table->string('district_name')->nullable()->after('district_code');
            $table->index(['province_code', 'regency_code', 'district_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_province_code_regency_code_district_code_index');
            $table->dropColumn(['province_code', 'province_name', 'regency_code', 'regency_name', 'district_code', 'district_name']);
        });
    }
};
