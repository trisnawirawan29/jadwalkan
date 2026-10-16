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
        Schema::create('sport_group_user', function (Blueprint $table): void {
            $table->foreignId('sport_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('invited');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->primary(['sport_group_id', 'user_id']);
            $table->index(['sport_group_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sport_group_user');
    }
};
