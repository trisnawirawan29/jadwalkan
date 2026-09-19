<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BusinessPlace;
use App\Models\BusinessService;
use App\Models\ServiceSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ProviderFinancialReportTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_provider_can_view_monthly_financial_report_and_filter_transactions(): void
    {
        $provider = User::factory()->create(['role' => 'provider']);
        $otherProvider = User::factory()->create(['role' => 'provider']);
        $customer = User::factory()->create(['role' => 'user', 'name' => 'Pelanggan Laporan']);
        $place = BusinessPlace::factory()->create(['provider_id' => $provider->id, 'name' => 'Tempat Laporan']);
        $service = BusinessService::factory()->create(['business_place_id' => $place->id, 'name' => 'Layanan Bulanan']);
        $schedule = ServiceSchedule::factory()->create(['business_service_id' => $service->id]);
        $firstDate = now()->startOfMonth()->addDays(2);
        $secondDate = now()->startOfMonth()->addDays(8);
        $firstBooking = Booking::factory()->create([
            'user_id' => $customer->id,
            'business_service_id' => $service->id,
            'service_schedule_id' => $schedule->id,
            'booking_date' => $firstDate,
            'status' => 'confirmed',
            'total_cost' => 125000,
        ]);
        Booking::factory()->create([
            'user_id' => $customer->id,
            'business_service_id' => $service->id,
            'service_schedule_id' => $schedule->id,
            'booking_date' => $secondDate,
            'status' => 'confirmed',
            'total_cost' => 175000,
        ]);
        Booking::factory()->create([
            'user_id' => $customer->id,
            'business_service_id' => $service->id,
            'service_schedule_id' => $schedule->id,
            'booking_date' => $secondDate,
            'status' => 'held',
            'total_cost' => 500000,
        ]);
        $otherPlace = BusinessPlace::factory()->create(['provider_id' => $otherProvider->id]);
        $otherService = BusinessService::factory()->create(['business_place_id' => $otherPlace->id]);
        $otherSchedule = ServiceSchedule::factory()->create(['business_service_id' => $otherService->id]);
        Booking::factory()->create([
            'user_id' => $customer->id,
            'business_service_id' => $otherService->id,
            'service_schedule_id' => $otherSchedule->id,
            'booking_date' => $firstDate,
            'status' => 'confirmed',
            'total_cost' => 900000,
        ]);

        $this->actingAs($provider)
            ->get(route('provider.reports.financial', ['month' => now()->format('Y-m')]))
            ->assertOk()
            ->assertSee('Rp 300.000')
            ->assertSee('2')
            ->assertSee($firstBooking->booking_code)
            ->assertSee('Tempat Laporan')
            ->assertDontSee('Rp 900.000');

        $this->actingAs($provider)
            ->get(route('provider.reports.financial', [
                'month' => now()->format('Y-m'),
                'booking_date' => $firstDate->format('Y-m-d'),
            ]))
            ->assertOk()
            ->assertSee('Rp 125.000')
            ->assertDontSee('Rp 175.000');
    }

    public function test_provider_staff_cannot_view_financial_report(): void
    {
        $provider = User::factory()->create(['role' => 'provider']);
        $staff = User::factory()->create(['role' => 'provider_staff', 'provider_id' => $provider->id]);

        $this->actingAs($staff)
            ->get(route('provider.reports.financial'))
            ->assertForbidden();
    }
}
