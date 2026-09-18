<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['google_client_id', 'google_client_secret', 'google_api_key', 'google_redirect_uri'] as $key) {
            DB::table('settings')->updateOrInsert(['key' => $key], ['value' => '']);
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', ['google_client_id', 'google_client_secret', 'google_api_key', 'google_redirect_uri'])->delete();
    }
};
