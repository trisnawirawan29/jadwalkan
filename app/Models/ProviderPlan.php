<?php

namespace App\Models;

use Database\Factories\ProviderPlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description', 'payment_instruction', 'max_business_places', 'max_services_per_place', 'monthly_price', 'max_bookings_per_month', 'is_active', 'is_free'])]
class ProviderPlan extends Model
{
    /** @use HasFactory<ProviderPlanFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'max_business_places' => 'integer',
            'max_services_per_place' => 'integer',
            'monthly_price' => 'decimal:2',
            'max_bookings_per_month' => 'integer',
            'is_active' => 'boolean',
            'is_free' => 'boolean',
        ];
    }

    public function isFree(): bool
    {
        return $this->is_free;
    }

    public function providers(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function upgradeRequests(): HasMany
    {
        return $this->hasMany(ProviderPlanUpgrade::class);
    }
}
