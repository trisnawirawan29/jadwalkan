<?php

namespace Database\Factories;

use App\Models\BusinessCategory;
use App\Models\SportGroup;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SportGroup>
 */
class SportGroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['creator_id' => User::factory(), 'business_category_id' => BusinessCategory::factory(), 'level' => 'beginner', 'name' => fake()->sentence(3), 'description' => fake()->sentence(), 'activity_date' => Carbon::tomorrow(), 'start_time' => '18:00', 'end_time' => '20:00', 'max_members' => 4, 'status' => 'open'];
    }
}
