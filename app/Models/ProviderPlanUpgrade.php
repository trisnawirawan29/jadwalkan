<?php

namespace App\Models;

use Database\Factories\ProviderPlanUpgradeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['provider_id', 'provider_plan_id', 'amount', 'is_prorated', 'proration_remaining_days', 'proration_total_days', 'service_started_at', 'service_expires_at', 'payment_method', 'payment_proof', 'status', 'rejection_reason', 'reviewed_by', 'reviewed_at'])]
class ProviderPlanUpgrade extends Model
{
    /** @use HasFactory<ProviderPlanUpgradeFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'is_prorated' => 'boolean', 'proration_remaining_days' => 'integer', 'proration_total_days' => 'integer', 'service_started_at' => 'datetime', 'service_expires_at' => 'datetime', 'reviewed_at' => 'datetime'];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function providerPlan(): BelongsTo
    {
        return $this->belongsTo(ProviderPlan::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
