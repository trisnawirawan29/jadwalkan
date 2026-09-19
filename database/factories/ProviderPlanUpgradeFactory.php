<?php

namespace Database\Factories;

use App\Models\ProviderPlan;
use App\Models\ProviderPlanUpgrade;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProviderPlanUpgrade>
 */
class ProviderPlanUpgradeFactory extends Factory
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
            'provider_plan_id' => ProviderPlan::factory(),
            'amount' => 299000,
            'payment_method' => 'bank_transfer',
            'payment_proof' => null,
            'status' => 'pending',
            'rejection_reason' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ];
    }
}
