<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['business_service_id', 'closure_date', 'note', 'is_active'])]
class ServiceClosure extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['closure_date' => 'date', 'is_active' => 'boolean'];
    }

    public function businessService(): BelongsTo
    {
        return $this->belongsTo(BusinessService::class);
    }

    protected function formattedDate(): Attribute
    {
        return Attribute::get(fn (): string => $this->closure_date?->translatedFormat('l, d F Y') ?? '-');
    }
}
