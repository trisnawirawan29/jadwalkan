<?php

namespace App\Models;

use Database\Factories\SportGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['creator_id', 'business_category_id', 'level', 'selected_business_service_id', 'name', 'description', 'activity_date', 'start_time', 'end_time', 'max_members', 'status'])]
class SportGroup extends Model
{
    /** @use HasFactory<SportGroupFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['activity_date' => 'date', 'max_members' => 'integer'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function businessCategory(): BelongsTo
    {
        return $this->belongsTo(BusinessCategory::class);
    }

    public function selectedBusinessService(): BelongsTo
    {
        return $this->belongsTo(BusinessService::class, 'selected_business_service_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'sport_group_user')->withPivot(['status', 'responded_at'])->withTimestamps();
    }
}
