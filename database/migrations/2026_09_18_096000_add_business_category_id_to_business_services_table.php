<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_services', function (Blueprint $table) {
            $table->foreignId('business_category_id')->nullable()->after('business_place_id')->constrained('business_categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('business_services', function (Blueprint $table) {
            $table->dropForeign(['business_category_id']);
            $table->dropColumn('business_category_id');
        });
    }
};
