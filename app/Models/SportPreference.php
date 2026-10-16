<?php

namespace App\Models;

use Database\Factories\SportPreferenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'business_category_id', 'level', 'day_of_week', 'start_time', 'end_time'])]
class SportPreference extends Model
{
    /** @use HasFactory<SportPreferenceFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['day_of_week' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function businessCategory(): BelongsTo
    {
        return $this->belongsTo(BusinessCategory::class);
    }
}
