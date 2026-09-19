<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BusinessPlace;
use App\Models\BusinessService;
use App\Models\ServiceHourlyPrice;
use App\Models\ServiceSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_authenticated_user_can_hold_a_matching_schedule_for_ten_minutes(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $provider = User::factory()->create(['role' => 'provider']);
        $place = BusinessPlace::factory()->create(['provider_id' => $provider->id]);
        $service = BusinessService::factory()->create(['business_place_id' => $place->id]);
        ServiceHourlyPrice::factory()->createMany([
            ['business_service_id' => $service->id, 'day_of_week' => 1, 'start_time' => '08:00', 'price' => 120000],
            ['business_service_id' => $service->id, 'day_of_week' => 1, 'start_time' => '09:00', 'price' => 150000],
        ]);
        $schedule = ServiceSchedule::factory()->create(['business_service_id' => $service->id, 'day_of_week' => 1]);
        $bookingDate = today()->next(Carbon::MONDAY);

        $response = $this->actingAs($user)->post(route('bookings.store', $place), [
            'service_schedule_id' => $schedule->id,
            'booking_date' => $bookingDate->toDateString(),
            'start_time' => '08:00',
            'end_time' => '10:00',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bookings', [
            'user_id' => $user->id,
            'service_schedule_id' => $schedule->id,
            'status' => 'held',
            'total_cost' => '270000.00',
        ]);
        $this->assertTrue(Booking::latest('id')->firstOrFail()->expires_at->between(now()->addMinutes(9), now()->addMinutes(11)));
    }

    public function test_provider_confirms_booking_after_verifying_payment_proof(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'user']);
        $provider = User::factory()->create(['role' => 'provider']);
        $place = BusinessPlace::factory()->create(['provider_id' => $provider->id]);
        $service = BusinessService::factory()->create(['business_place_id' => $place->id]);
        $schedule = ServiceSchedule::factory()->create(['business_service_id' => $service->id]);
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'business_service_id' => $service->id,
            'service_schedule_id' => $schedule->id,
        ]);

        $this->actingAs($user)->post(route('bookings.payment-proof', $booking), [
            'payment_proof' => UploadedFile::fake()->image('payment-proof.jpg'),
        ])->assertRedirect();
        $booking->refresh();
        $this->assertSame('payment_submitted', $booking->status);
        Storage::disk('public')->assertExists($booking->payment_proof);

        $this->actingAs($provider)->patch(route('provider.bookings.approve', $booking))->assertRedirect();

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'confirmed']);

        $this->actingAs($user)->get(route('bookings.show', $booking))->assertOk()->assertSee('booking-qrcode');
        $this->actingAs($provider)->get(URL::signedRoute('provider.bookings.check-in', $booking))->assertOk()->assertSee($booking->booking_code);
        $this->actingAs($provider)->patch(route('provider.bookings.check-in.confirm', $booking))->assertRedirect();

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'confirmed', 'checked_in_by' => $provider->id]);
    }

    public function test_provider_can_open_the_qr_scanner(): void
    {
        $provider = User::factory()->create(['role' => 'provider']);

        $this->actingAs($provider)
            ->get(route('provider.bookings.scan'))
            ->assertOk()
            ->assertSee('booking-qr-reader');
    }

    public function test_provider_booking_list_defaults_to_upcoming_and_can_filter_past_date(): void
    {
        $provider = User::factory()->create(['role' => 'provider']);
        $customer = User::factory()->create(['role' => 'user']);
        $place = BusinessPlace::factory()->create(['provider_id' => $provider->id]);
        $service = BusinessService::factory()->create(['business_place_id' => $place->id]);
        $schedule = ServiceSchedule::factory()->create(['business_service_id' => $service->id]);
        $pastBooking = Booking::factory()->create([
            'user_id' => $customer->id,
            'business_service_id' => $service->id,
            'service_schedule_id' => $schedule->id,
            'booking_code' => 'BK-PAST-0001',
            'booking_date' => today()->subDay(),
            'status' => 'confirmed',
        ]);
        $upcomingBooking = Booking::factory()->create([
            'user_id' => $customer->id,
            'business_service_id' => $service->id,
            'service_schedule_id' => $schedule->id,
            'booking_code' => 'BK-NEXT-0001',
            'booking_date' => today()->addDay(),
            'status' => 'confirmed',
        ]);

        $this->actingAs($provider)
            ->get(route('provider.bookings.index'))
            ->assertSee($upcomingBooking->booking_code)
            ->assertDontSee($pastBooking->booking_code);

        $this->actingAs($provider)
            ->get(route('provider.bookings.index', ['booking_date' => today()->subDay()->toDateString()]))
            ->assertSee($pastBooking->booking_code)
            ->assertDontSee($upcomingBooking->booking_code);
    }

    public function test_user_booking_list_defaults_to_nearest_upcoming_and_exposes_history_filter(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $provider = User::factory()->create(['role' => 'provider']);
        $place = BusinessPlace::factory()->create(['provider_id' => $provider->id]);
        $service = BusinessService::factory()->create(['business_place_id' => $place->id]);
        $schedule = ServiceSchedule::factory()->create(['business_service_id' => $service->id]);
        $pastBooking = Booking::factory()->create([
            'user_id' => $user->id,
            'business_service_id' => $service->id,
            'service_schedule_id' => $schedule->id,
            'booking_code' => 'BK-USER-PAST',
            'booking_date' => today()->subDay(),
            'status' => 'confirmed',
        ]);
        $upcomingBooking = Booking::factory()->create([
            'user_id' => $user->id,
            'business_service_id' => $service->id,
            'service_schedule_id' => $schedule->id,
            'booking_code' => 'BK-USER-NEXT',
            'booking_date' => today()->addDay(),
            'status' => 'confirmed',
        ]);

        $this->actingAs($user)
            ->get(route('bookings.index'))
            ->assertOk()
            ->assertSee($upcomingBooking->booking_code)
            ->assertSee($pastBooking->booking_code);

        $this->actingAs($user)
            ->get(route('bookings.index', ['view' => 'history']))
            ->assertOk()
            ->assertSee($pastBooking->booking_code)
            ->assertSee($upcomingBooking->booking_code);

        $this->actingAs($user)
            ->get(route('bookings.index', ['display' => 'list']))
            ->assertOk()
            ->assertSee($upcomingBooking->booking_code)
            ->assertDontSee($pastBooking->booking_code)
            ->assertSee('booking-search');
    }

    public function test_an_active_hold_prevents_another_user_from_booking_the_same_schedule(): void
    {
        $firstUser = User::factory()->create(['role' => 'user']);
        $secondUser = User::factory()->create(['role' => 'user']);
        $provider = User::factory()->create(['role' => 'provider']);
        $place = BusinessPlace::factory()->create(['provider_id' => $provider->id]);
        $service = BusinessService::factory()->create(['business_place_id' => $place->id]);
        $schedule = ServiceSchedule::factory()->create(['business_service_id' => $service->id, 'day_of_week' => 1]);
        $bookingDate = today()->next(Carbon::MONDAY);

        $this->actingAs($firstUser)->post(route('bookings.store', $place), [
            'service_schedule_id' => $schedule->id,
            'booking_date' => $bookingDate->toDateString(),
            'start_time' => '08:00',
            'end_time' => '10:00',
        ]);

        $this->actingAs($secondUser)->post(route('bookings.store', $place), [
            'service_schedule_id' => $schedule->id,
            'booking_date' => $bookingDate->toDateString(),
            'start_time' => '08:00',
            'end_time' => '10:00',
        ])->assertSessionHasErrors('service_schedule_id');
    }

    public function test_expired_held_bookings_are_deleted_by_cleanup_command(): void
    {
        $expiredBooking = Booking::factory()->create(['status' => 'held', 'expires_at' => now()->subMinute()]);
        $activeBooking = Booking::factory()->create(['status' => 'held', 'expires_at' => now()->addMinutes(10)]);

        $this->artisan('bookings:cleanup-expired')
            ->expectsOutput('Deleted 1 expired held booking(s).')
            ->assertSuccessful();

        $this->assertDatabaseMissing('bookings', ['id' => $expiredBooking->id]);
        $this->assertDatabaseHas('bookings', ['id' => $activeBooking->id]);
    }

    public function test_user_is_redirected_when_opening_an_expired_booking_detail(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $booking = Booking::factory()->create(['user_id' => $user->id, 'status' => 'held', 'expires_at' => now()->subMinute()]);

        $this->actingAs($user)
            ->get(route('bookings.show', $booking))
            ->assertRedirect(route('bookings.index'))
            ->assertSessionHasErrors('booking');

        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
    }

    public function test_guest_must_login_before_starting_a_booking(): void
    {
        $place = BusinessPlace::factory()->create();

        $this->post(route('bookings.store', $place), [
            'service_schedule_id' => 1,
            'booking_date' => today()->addDay()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '09:00',
        ])->assertRedirect(route('login'));
    }
}
