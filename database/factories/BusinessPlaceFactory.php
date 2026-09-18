<?php

namespace Database\Factories;

use App\Models\BusinessPlace;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessPlace>
 */
class BusinessPlaceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider_id' => User::factory()->state(['role' => 'provider']),
            'name' => fake()->company().' Sport Center',
            'description' => fake()->sentence(),
            'address' => fake()->address(),
            'province_code' => '51',
            'province_name' => 'Bali',
            'regency_code' => '51.01',
            'regency_name' => 'Kabupaten Jembrana',
            'district_code' => '51.01.01',
            'district_name' => 'Negara',
            'phone' => fake()->numerify('08##########'),
            'is_active' => true,
        ];
    }
}
