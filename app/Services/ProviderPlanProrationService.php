<?php

namespace App\Services;

use App\Models\ProviderPlan;
use App\Models\User;
use Carbon\CarbonInterface;

class ProviderPlanProrationService
{
    /**
     * @return array{
     *     amount: int,
     *     is_prorated: bool,
     *     current_plan_name: ?string,
     *     current_monthly_price: float,
     *     target_monthly_price: float,
     *     price_difference: float,
     *     remaining_days: int,
     *     total_days: int,
     *     current_expires_at: ?CarbonInterface,
     * }
     */
    public function quote(User $provider, ProviderPlan $targetPlan): array
    {
        $currentPlan = $provider->providerPlan;
        $expiresAt = $provider->provider_plan_expires_at;
        $startsAt = $provider->provider_plan_started_at;
        $targetPrice = (float) $targetPlan->monthly_price;

        if (! $currentPlan || ! $startsAt || ! $expiresAt || ! $expiresAt->isFuture()) {
            return [
                'amount' => (int) round($targetPrice),
                'is_prorated' => false,
                'current_plan_name' => $currentPlan?->name,
                'current_monthly_price' => (float) ($currentPlan?->monthly_price ?? 0),
                'target_monthly_price' => $targetPrice,
                'price_difference' => $targetPrice - (float) ($currentPlan?->monthly_price ?? 0),
                'remaining_days' => 0,
                'total_days' => 0,
                'current_expires_at' => $expiresAt,
            ];
        }

        $periodStart = $startsAt->copy()->startOfDay();
        $periodEnd = $expiresAt->copy()->startOfDay();
        $today = now()->startOfDay();
        $totalDays = max(1, $periodStart->diffInDays($periodEnd) + 1);
        $remainingDays = max(1, min($totalDays, $today->diffInDays($periodEnd) + 1));
        $currentPrice = (float) $currentPlan->monthly_price;
        $priceDifference = max(0, $targetPrice - $currentPrice);

        return [
            'amount' => (int) round($priceDifference * $remainingDays / $totalDays),
            'is_prorated' => true,
            'current_plan_name' => $currentPlan->name,
            'current_monthly_price' => $currentPrice,
            'target_monthly_price' => $targetPrice,
            'price_difference' => $priceDifference,
            'remaining_days' => $remainingDays,
            'total_days' => $totalDays,
            'current_expires_at' => $expiresAt,
        ];
    }
}
