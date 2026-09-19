<?php

namespace Database\Factories;

use App\Models\BusinessService;
use App\Models\ServiceHourlyPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceHourlyPrice>
 */
class ServiceHourlyPriceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_service_id' => BusinessService::factory(),
            'day_of_week' => fake()->numberBetween(1, 7),
            'start_time' => fake()->randomElement(['08:00', '09:00', '18:00']),
            'price' => 150000,
        ];
    }
}
