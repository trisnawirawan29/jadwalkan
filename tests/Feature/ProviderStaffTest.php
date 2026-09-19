<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BusinessPlace;
use App\Models\BusinessService;
use App\Models\ServiceSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ProviderStaffTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_provider_can_register_staff_scoped_to_the_provider(): void
    {
        $provider = User::factory()->create(['role' => 'provider']);

        $this->actingAs($provider)->post(route('provider.staff.store'), [
            'name' => 'Pegawai Operasional',
            'email' => 'pegawai@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'pegawai@example.test',
            'role' => 'provider_staff',
            'provider_id' => $provider->id,
        ]);
    }

    public function test_staff_can_monitor_only_their_provider_bookings(): void
    {
        $provider = User::factory()->create(['role' => 'provider']);
        $otherProvider = User::factory()->create(['role' => 'provider']);
        $staff = User::factory()->create(['role' => 'provider_staff', 'provider_id' => $provider->id]);
        $customer = User::factory()->create(['role' => 'user']);
        $place = BusinessPlace::factory()->create(['provider_id' => $provider->id]);
        $service = BusinessService::factory()->create(['business_place_id' => $place->id]);
        $schedule = ServiceSchedule::factory()->create(['business_service_id' => $service->id]);
        $booking = Booking::factory()->create([
            'user_id' => $customer->id,
            'business_service_id' => $service->id,
            'service_schedule_id' => $schedule->id,
            'booking_date' => today()->addDay(),
            'status' => 'confirmed',
        ]);
        $otherPlace = BusinessPlace::factory()->create(['provider_id' => $otherProvider->id]);

        $this->actingAs($staff)->get(route('provider.bookings.index'))->assertOk()->assertSee($booking->booking_code);
        $this->actingAs($staff)->get(route('provider.business-places.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('provider.staff.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('bookings.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('provider.business-places.show', $otherPlace))->assertForbidden();
    }
}
