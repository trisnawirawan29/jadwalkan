<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['business_place_id', 'business_category_id', 'name', 'type', 'description', 'price_per_hour', 'hourly_prices', 'cover_image', 'is_active'])]
class BusinessService extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'price_per_hour' => 'decimal:2', 'hourly_prices' => 'array'];
    }

    public function getCoverImageUrlAttribute(): ?string
    {
        if (! $this->cover_image || ! Storage::disk('public')->exists($this->cover_image)) {
            return null;
        }

        return Storage::disk('public')->url($this->cover_image);
    }

    public function businessPlace(): BelongsTo
    {
        return $this->belongsTo(BusinessPlace::class);
    }

    public function businessCategory(): BelongsTo
    {
        return $this->belongsTo(BusinessCategory::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ServiceSchedule::class);
    }

    public function hourlyPrices(): HasMany
    {
        return $this->hasMany(ServiceHourlyPrice::class)->orderBy('day_of_week')->orderBy('start_time');
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function getHourlyPriceMapAttribute(): array
    {
        if (! $this->relationLoaded('hourlyPrices')) {
            return [];
        }

        return $this->hourlyPrices
            ->groupBy('day_of_week')
            ->map(fn ($prices): array => $prices->mapWithKeys(fn (ServiceHourlyPrice $price): array => [substr($price->start_time, 0, 5) => (string) $price->price])->all())
            ->all();
    }

    public function closures(): HasMany
    {
        return $this->hasMany(ServiceClosure::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
