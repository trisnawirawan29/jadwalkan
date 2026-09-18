<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('job_title')->nullable()->after('role');
            $table->string('phone', 30)->nullable()->after('job_title');
            $table->string('location')->nullable()->after('phone');
            $table->date('birth_date')->nullable()->after('location');
            $table->string('website')->nullable()->after('birth_date');
            $table->text('bio')->nullable()->after('website');
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['job_title', 'phone', 'location', 'birth_date', 'website', 'bio']));
    }
};
