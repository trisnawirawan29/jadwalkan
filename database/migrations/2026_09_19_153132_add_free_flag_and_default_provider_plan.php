<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('provider_plans', function (Blueprint $table): void {
            $table->boolean('is_free')->default(false)->after('slug');
        });
        DB::table('provider_plans')->updateOrInsert(
            ['slug' => 'free'],
            [
                'name' => 'Free',
                'description' => 'Paket awal untuk provider baru.',
                'payment_instruction' => null,
                'max_business_places' => 1,
                'max_services_per_place' => 1,
                'monthly_price' => 0,
                'max_bookings_per_month' => 10,
                'is_free' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
        $freePlanId = DB::table('provider_plans')->where('slug', 'free')->value('id');
        DB::table('users')->where('role', 'provider')->whereNull('provider_plan_id')->update([
            'provider_plan_id' => $freePlanId,
            'provider_plan_started_at' => now(),
            'provider_plan_expires_at' => null,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('provider_plans')->where('slug', 'free')->delete();
        Schema::table('provider_plans', function (Blueprint $table): void {
            $table->dropColumn('is_free');
        });
    }
};
