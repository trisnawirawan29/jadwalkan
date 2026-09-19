<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ([
            'provider_plan_bank_name',
            'provider_plan_bank_account_name',
            'provider_plan_bank_account_number',
            'provider_plan_qris_image',
        ] as $key) {
            DB::table('settings')->updateOrInsert(['key' => $key], ['value' => '']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'provider_plan_bank_name',
            'provider_plan_bank_account_name',
            'provider_plan_bank_account_number',
            'provider_plan_qris_image',
        ])->delete();
    }
};
