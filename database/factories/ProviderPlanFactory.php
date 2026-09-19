<?php

namespace Database\Factories;

use App\Models\ProviderPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProviderPlan>
 */
class ProviderPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'slug' => fake()->unique()->slug(2),
            'description' => fake()->sentence(),
            'max_business_places' => 3,
            'max_services_per_place' => 5,
            'monthly_price' => 299000,
            'max_bookings_per_month' => 100,
            'is_active' => true,
        ];
    }
}
