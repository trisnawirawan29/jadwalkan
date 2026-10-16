<?php

namespace Database\Factories;

use App\Models\BusinessCategory;
use App\Models\SportCommunity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SportCommunity>
 */
class SportCommunityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['business_category_id' => BusinessCategory::factory(), 'creator_id' => User::factory(), 'name' => 'Komunitas '.fake()->word(), 'description' => fake()->sentence(), 'is_active' => true];
    }
}
