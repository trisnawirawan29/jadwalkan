<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->where('key', 'google_maps_api_key')->delete();
    }

    public function down(): void
    {
        DB::table('settings')->updateOrInsert(['key' => 'google_maps_api_key'], ['value' => '']);
    }
};
