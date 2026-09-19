<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'email', 'password', 'avatar', 'role', 'provider_id', 'job_title', 'phone', 'location', 'province_code', 'province_name', 'regency_code', 'regency_name', 'district_code', 'district_name', 'birth_date', 'website', 'bio', 'payment_bank_name', 'payment_bank_account_name', 'payment_bank_account_number', 'payment_qris_image', 'google_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements CanResetPassword
{
    /** @use HasFactory<UserFactory> */
    use CanResetPasswordTrait, HasFactory, Notifiable;

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isProvider(): bool
    {
        return $this->hasRole('provider');
    }

    public function isProviderStaff(): bool
    {
        return $this->hasRole('provider_staff');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(self::class, 'provider_id');
    }

    public function staffMembers(): HasMany
    {
        return $this->hasMany(self::class, 'provider_id')->where('role', 'provider_staff');
    }

    public function providerOwnerId(): int
    {
        return $this->isProviderStaff() ? (int) $this->provider_id : (int) $this->id;
    }

    public function businessPlaces(): HasMany
    {
        return $this->hasMany(BusinessPlace::class, 'provider_id');
    }

    public function providerApplications(): HasMany
    {
        return $this->hasMany(ProviderApplication::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (! $this->avatar || ! Storage::disk('public')->exists($this->avatar)) {
            return null;
        }

        return Storage::disk('public')->url($this->avatar);
    }

    public function getPaymentQrisImageUrlAttribute(): ?string
    {
        if (! $this->payment_qris_image || ! Storage::disk('public')->exists($this->payment_qris_image)) {
            return null;
        }

        return Storage::disk('public')->url($this->payment_qris_image);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'provider_id' => 'integer',
        ];
    }
}
