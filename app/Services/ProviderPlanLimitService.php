<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BusinessPlace;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class ProviderPlanLimitService
{
    public function ensureCanCreateBusiness(User $provider): void
    {
        if ($message = $this->businessCreationLimitMessage($provider)) {
            throw ValidationException::withMessages(['name' => $message]);
        }
    }

    public function businessCreationLimitMessage(User $provider): ?string
    {
        $plan = $provider->providerPlan;

        if ($plan && $provider->provider_plan_expires_at?->isPast()) {
            $this->deactivateExpiredBusinesses($provider);

            return "Masa aktif paket {$plan->name} telah berakhir. Upgrade paket untuk menambahkan atau mengaktifkan bisnis kembali.";
        }

        if ($plan && $provider->businessPlaces()->count() >= $plan->max_business_places) {
            return "Paket {$plan->name} membatasi hingga {$plan->max_business_places} bisnis. Anda sudah menggunakan seluruh kuota. Silakan upgrade paket untuk menambahkan bisnis baru.";
        }

        return null;
    }

    public function ensureCanCreateService(BusinessPlace $businessPlace): void
    {
        $provider = $businessPlace->provider;
        $this->ensurePlanIsActive($provider);
        $plan = $provider?->providerPlan;

        if ($plan && $businessPlace->services()->count() >= $plan->max_services_per_place) {
            throw ValidationException::withMessages(['name' => "Paket {$plan->name} membatasi hingga {$plan->max_services_per_place} layanan per bisnis."]);
        }
    }

    public function ensureCanAcceptBooking(BusinessPlace $businessPlace, Carbon $bookingDate): void
    {
        Booking::cleanupExpiredHolds();
        $provider = $businessPlace->provider;
        $this->ensurePlanIsActive($provider);
        $plan = $provider?->providerPlan;

        if (! $plan) {
            return;
        }

        $bookingsThisMonth = Booking::query()
            ->whereBetween('booking_date', [$bookingDate->copy()->startOfMonth()->toDateString(), $bookingDate->copy()->endOfMonth()->toDateString()])
            ->whereIn('status', ['held', 'payment_submitted', 'confirmed'])
            ->whereHas('businessService.businessPlace', fn ($query) => $query->where('provider_id', $provider->id))
            ->count();

        if ($bookingsThisMonth >= $plan->max_bookings_per_month) {
            throw ValidationException::withMessages(['booking_date' => "Paket {$plan->name} telah mencapai batas {$plan->max_bookings_per_month} booking per bulan."]);
        }
    }

    public function deactivateExpiredBusinesses(User $provider): int
    {
        if (! $provider->provider_plan_expires_at?->isPast()) {
            return 0;
        }

        return $provider->businessPlaces()->where('is_active', true)->update(['is_active' => false]);
    }

    private function ensurePlanIsActive(?User $provider): void
    {
        if ($provider?->providerPlan && $provider->provider_plan_expires_at?->isPast()) {
            $this->deactivateExpiredBusinesses($provider);
            throw ValidationException::withMessages(['provider_plan' => 'Masa aktif paket provider telah berakhir. Silakan upgrade paket untuk mengaktifkan kembali bisnis Anda.']);
        }
    }
}
