<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['provider_id', 'name', 'business_category_id', 'cover_image', 'description', 'address', 'google_maps_url', 'latitude', 'longitude', 'province_code', 'province_name', 'regency_code', 'regency_name', 'district_code', 'district_name', 'phone', 'bank_payment_enabled', 'qris_payment_enabled', 'is_active'])]
class BusinessPlace extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'bank_payment_enabled' => 'boolean', 'qris_payment_enabled' => 'boolean', 'latitude' => 'float', 'longitude' => 'float'];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function businessCategory(): BelongsTo
    {
        return $this->belongsTo(BusinessCategory::class);
    }

    public function businessCategories(): BelongsToMany
    {
        return $this->belongsToMany(BusinessCategory::class, 'business_place_business_category')->withTimestamps();
    }

    public function services(): HasMany
    {
        return $this->hasMany(BusinessService::class);
    }

    public function getCoverImageUrlAttribute(): ?string
    {
        if (! $this->cover_image || ! Storage::disk('public')->exists($this->cover_image)) {
            return null;
        }

        return Storage::disk('public')->url($this->cover_image);
    }

    public function getQrisImageUrlAttribute(): ?string
    {
        if (! $this->qris_image || ! Storage::disk('public')->exists($this->qris_image)) {
            return null;
        }

        return Storage::disk('public')->url($this->qris_image);
    }
}
