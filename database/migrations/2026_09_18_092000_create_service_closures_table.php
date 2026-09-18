<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('service_closures')) {
            return;
        }

        Schema::create('service_closures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_service_id')->constrained()->cascadeOnDelete();
            $table->date('closure_date');
            $table->string('note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['business_service_id', 'closure_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_closures');
    }
};
