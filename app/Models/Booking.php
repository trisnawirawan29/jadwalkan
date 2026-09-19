<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'business_service_id', 'service_schedule_id', 'booking_date', 'start_time', 'end_time', 'total_cost', 'booking_code', 'status', 'expires_at', 'paid_at', 'payment_proof', 'payment_submitted_at', 'verified_at', 'verification_note', 'checked_in_at', 'checked_in_by', 'notes'])]
class Booking extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'payment_submitted_at' => 'datetime',
            'verified_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'total_cost' => 'decimal:2',
        ];
    }

    public static function cleanupExpiredHolds(): int
    {
        return static::query()
            ->where('status', 'held')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->delete();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function businessService(): BelongsTo
    {
        return $this->belongsTo(BusinessService::class);
    }

    public function serviceSchedule(): BelongsTo
    {
        return $this->belongsTo(ServiceSchedule::class);
    }

    public function isHeld(): bool
    {
        return $this->status === 'held' && $this->expires_at?->isFuture();
    }
}
