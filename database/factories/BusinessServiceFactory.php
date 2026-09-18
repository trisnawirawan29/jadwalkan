<?php

namespace Database\Factories;

use App\Models\BusinessPlace;
use App\Models\BusinessService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessService>
 */
class BusinessServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_place_id' => BusinessPlace::factory(),
            'name' => fake()->randomElement(['Lapangan Tenis 1', 'Lapangan Futsal A', 'Lapangan Bulutangkis 1']),
            'type' => fake()->randomElement(['Tenis', 'Futsal', 'Bulutangkis']),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
