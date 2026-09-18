<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_places', function (Blueprint $table) {
            $table->foreignId('business_category_id')->nullable()->after('name')->constrained('business_categories')->nullOnDelete();
            $table->index('business_category_id');
        });
    }

    public function down(): void
    {
        Schema::table('business_places', function (Blueprint $table) {
            $table->dropForeign(['business_category_id']);
            $table->dropIndex(['business_category_id']);
            $table->dropColumn('business_category_id');
        });
    }
};
