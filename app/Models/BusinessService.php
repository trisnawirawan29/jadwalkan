<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['business_place_id', 'business_category_id', 'name', 'type', 'description', 'cover_image', 'is_active'])]
class BusinessService extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
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

    public function closures(): HasMany
    {
        return $this->hasMany(ServiceClosure::class);
    }
}
