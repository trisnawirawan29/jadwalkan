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
        Schema::create('service_hourly_prices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('business_service_id')->constrained('business_services')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time');
            $table->decimal('price', 12, 2);
            $table->timestamps();
            $table->unique(['business_service_id', 'day_of_week', 'start_time'], 'service_hourly_prices_service_day_time_unique');
        });

        DB::table('business_services')->select(['id', 'hourly_prices'])->orderBy('id')->chunkById(100, function ($services): void {
            $rows = [];
            $now = now();

            foreach ($services as $service) {
                $hourlyPrices = json_decode($service->hourly_prices ?: '{}', true);
                if (! is_array($hourlyPrices)) {
                    continue;
                }

                foreach ($hourlyPrices as $day => $prices) {
                    if (! preg_match('/^[1-7]$/', (string) $day) || ! is_array($prices)) {
                        continue;
                    }

                    foreach ($prices as $startTime => $price) {
                        if (! preg_match('/^(?:[01]\d|2[0-3]):00$/', (string) $startTime) || $price === null || $price === '') {
                            continue;
                        }

                        $rows[] = [
                            'business_service_id' => $service->id,
                            'day_of_week' => (int) $day,
                            'start_time' => $startTime.':00',
                            'price' => round((float) $price, 2),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
            }

            if ($rows !== []) {
                DB::table('service_hourly_prices')->insert($rows);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_hourly_prices');
    }
};
