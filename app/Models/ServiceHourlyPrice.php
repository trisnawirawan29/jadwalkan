<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['business_service_id', 'day_of_week', 'start_time', 'price'])]
class ServiceHourlyPrice extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['day_of_week' => 'integer', 'price' => 'decimal:2'];
    }

    public function businessService(): BelongsTo
    {
        return $this->belongsTo(BusinessService::class);
    }
}
