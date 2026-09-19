<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ProviderPlanLimitService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('provider-plans:deactivate-expired')]
#[Description('Disable business places owned by providers whose plan has expired')]
class DeactivateExpiredProviderPlans extends Command
{
    public function handle(ProviderPlanLimitService $planLimits): int
    {
        $disabledCount = 0;
        User::query()
            ->where('role', 'provider')
            ->whereNotNull('provider_plan_expires_at')
            ->where('provider_plan_expires_at', '<=', now())
            ->with('businessPlaces')
            ->each(function (User $provider) use ($planLimits, &$disabledCount): void {
                $disabledCount += $planLimits->deactivateExpiredBusinesses($provider);
            });

        $this->info("Disabled {$disabledCount} expired provider business(es).");

        return self::SUCCESS;
    }
}
