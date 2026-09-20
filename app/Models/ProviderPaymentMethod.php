<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['provider_id', 'type', 'bank_name', 'account_name', 'account_number', 'qris_image', 'is_active'])]
class ProviderPaymentMethod extends Model
{
    protected function casts(): array
    {
        return ['provider_id' => 'integer', 'is_active' => 'boolean'];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function isBankTransfer(): bool
    {
        return $this->type === 'bank_transfer';
    }

    public function isQris(): bool
    {
        return $this->type === 'qris';
    }

    public function getQrisImageUrlAttribute(): ?string
    {
        if (! $this->qris_image || ! Storage::disk('public')->exists($this->qris_image)) {
            return null;
        }

        return Storage::disk('public')->url($this->qris_image);
    }
}
