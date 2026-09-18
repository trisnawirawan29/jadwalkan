<?php

namespace Database\Factories;

use App\Models\BusinessService;
use App\Models\ServiceSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceSchedule>
 */
class ServiceScheduleFactory extends Factory
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
            'start_time' => '08:00',
            'end_time' => '22:00',
            'is_active' => true,
        ];
    }
}
