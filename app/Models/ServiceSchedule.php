<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['business_service_id', 'day_of_week', 'start_time', 'end_time', 'is_closed', 'is_active'])]
class ServiceSchedule extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['day_of_week' => 'integer', 'is_closed' => 'boolean', 'is_active' => 'boolean'];
    }

    public function businessService(): BelongsTo
    {
        return $this->belongsTo(BusinessService::class);
    }

    public function getDayNameAttribute(): string
    {
        $dayName = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ][$this->day_of_week] ?? 'Tidak diketahui';

        return __($dayName);
    }
}
