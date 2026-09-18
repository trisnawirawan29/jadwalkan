<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->insert([
            ['key' => 'sidebar_color', 'value' => '#1f2440'],
            ['key' => 'navbar_color', 'value' => '#ffffff'],
            ['key' => 'footer_color', 'value' => '#ffffff'],
            ['key' => 'google_client_id', 'value' => ''],
            ['key' => 'google_client_secret', 'value' => ''],
            ['key' => 'google_api_key', 'value' => ''],
            ['key' => 'google_redirect_uri', 'value' => ''],
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', ['sidebar_color', 'navbar_color', 'footer_color', 'google_client_id', 'google_client_secret', 'google_api_key', 'google_redirect_uri'])->delete();
    }
};
