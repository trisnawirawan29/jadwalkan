<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
        });

        DB::table('settings')->insert([
            ['key' => 'app_name', 'value' => 'NexaAdmin'],
            ['key' => 'app_tagline', 'value' => 'Platform administrasi modern'],
            ['key' => 'footer_text', 'value' => 'Semua hak dilindungi.'],
            ['key' => 'app_version', 'value' => '1.0.0'],
            ['key' => 'primary_color', 'value' => '#6c63ff'],
            ['key' => 'default_theme', 'value' => 'light'],
            ['key' => 'contact_email', 'value' => 'admin@example.com'],
            ['key' => 'timezone', 'value' => 'Asia/Makassar'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
