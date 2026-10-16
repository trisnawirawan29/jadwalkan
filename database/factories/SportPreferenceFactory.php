<?php

namespace Database\Factories;

use App\Models\BusinessCategory;
use App\Models\SportPreference;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SportPreference>
 */
class SportPreferenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'business_category_id' => BusinessCategory::query()->whereNotNull('parent_id')->value('id'), 'level' => 'beginner', 'day_of_week' => fake()->numberBetween(1, 7), 'start_time' => '18:00', 'end_time' => '20:00'];
    }
}
