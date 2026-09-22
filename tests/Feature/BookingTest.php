<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BusinessPlace;
use App\Models\BusinessService;
use App\Models\ServiceClosure;
use App\Models\ServiceHourlyPrice;
use App\Models\ServiceSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\DatabaseNotification;
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

    public function test_booking_uses_the_business_hold_duration(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $provider = User::factory()->create(['role' => 'provider']);
        $place = BusinessPlace::factory()->create(['provider_id' => $provider->id, 'hold_duration_minutes' => 25]);
        $service = BusinessService::factory()->create(['business_place_id' => $place->id]);
        $schedule = ServiceSchedule::factory()->create(['business_service_id' => $service->id, 'day_of_week' => Carbon::MONDAY]);
        $bookingDate = today()->next(Carbon::MONDAY);

        $this->actingAs($user)->post(route('bookings.store', $place), [
            'service_schedule_id' => $schedule->id,
            'booking_date' => $bookingDate->toDateString(),
            'start_time' => '08:00',
            'end_time' => '09:00',
        ])->assertRedirect();

        $this->assertTrue(Booking::latest('id')->firstOrFail()->expires_at->between(now()->addMinutes(24), now()->addMinutes(26)));
    }

    public function test_superadmin_can_hold_a_matching_schedule_for_ten_minutes(): void
    {
        $superadmin = User::factory()->create(['role' => 'superadmin']);
        $provider = User::factory()->create(['role' => 'provider']);
        $place = BusinessPlace::factory()->create(['provider_id' => $provider->id]);
        $service = BusinessService::factory()->create(['business_place_id' => $place->id]);
        $schedule = ServiceSchedule::factory()->create([
            'business_service_id' => $service->id,
            'day_of_week' => Carbon::MONDAY,
        ]);
        $bookingDate = today()->next(Carbon::MONDAY);

        $this->actingAs($superadmin)->post(route('bookings.store', $place), [
            'service_schedule_id' => $schedule->id,
            'booking_date' => $bookingDate->toDateString(),
            'start_time' => '08:00',
            'end_time' => '09:00',
        ])->assertRedirect();

        $this->assertDatabaseHas('bookings', [
            'user_id' => $superadmin->id,
            'status' => 'held',
        ]);
    }

    public function test_user_cannot_book_a_service_on_a_provider_closure_date(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $provider = User::factory()->create(['role' => 'provider']);
        $place = BusinessPlace::factory()->create(['provider_id' => $provider->id]);
        $service = BusinessService::factory()->create(['business_place_id' => $place->id]);
        $schedule = ServiceSchedule::factory()->create(['business_service_id' => $service->id, 'day_of_week' => Carbon::MONDAY]);
        $bookingDate = today()->next(Carbon::MONDAY);
        ServiceClosure::query()->create(['business_service_id' => $service->id, 'closure_date' => $bookingDate, 'is_active' => true]);

        $this->actingAs($user)->post(route('bookings.store', $place), [
            'service_schedule_id' => $schedule->id,
            'booking_date' => $bookingDate->toDateString(),
            'start_time' => '08:00',
            'end_time' => '09:00',
        ])->assertSessionHasErrors('booking_date');

        $this->assertDatabaseCount('bookings', 0);
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
        $providerNotification = DatabaseNotification::query()->where('notifiable_id', $user->id)->firstOrFail();
        $this->assertSame('provider', $providerNotification->data['source']);
        $this->assertSame($booking->id, $providerNotification->data['booking_id']);
        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('sidebar-notification-badge');

        $this->actingAs($user)->get(route('bookings.index'))->assertOk()->assertSee('sidebar-notification-badge');
        $this->assertNull($providerNotification->fresh()->read_at);

        $this->actingAs($user)->get(route('bookings.show', $booking))->assertOk()->assertSee('booking-qrcode');
        $this->assertNotNull($providerNotification->fresh()->read_at);
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

    public function test_provider_booking_list_highlights_held_and_rejected_rows(): void
    {
        $provider = User::factory()->create(['role' => 'provider']);
        $customer = User::factory()->create(['role' => 'user']);
        $place = BusinessPlace::factory()->create(['provider_id' => $provider->id]);
        $service = BusinessService::factory()->create(['business_place_id' => $place->id]);
        $schedule = ServiceSchedule::factory()->create(['business_service_id' => $service->id]);
        $heldBooking = Booking::factory()->create([
            'user_id' => $customer->id,
            'business_service_id' => $service->id,
            'service_schedule_id' => $schedule->id,
            'status' => 'held',
            'expires_at' => now()->addMinutes(10),
            'booking_code' => 'BK-HELD-ROW',
            'booking_date' => today()->addDay(),
        ]);
        $rejectedBooking = Booking::factory()->create([
            'user_id' => $customer->id,
            'business_service_id' => $service->id,
            'service_schedule_id' => $schedule->id,
            'status' => 'rejected',
            'booking_code' => 'BK-REJECTED-ROW',
            'booking_date' => today()->addDays(2),
        ]);
        $paymentSubmittedBooking = Booking::factory()->create([
            'user_id' => $customer->id,
            'business_service_id' => $service->id,
            'service_schedule_id' => $schedule->id,
            'status' => 'payment_submitted',
            'booking_code' => 'BK-PAYMENT-ROW',
            'booking_date' => today()->addDays(3),
        ]);

        $this->actingAs($provider)
            ->get(route('provider.bookings.index'))
            ->assertOk()
            ->assertSee('provider-booking-row is-held', false)
            ->assertSee('provider-booking-row is-rejected', false)
            ->assertSee('provider-booking-row is-payment-submitted', false)
            ->assertSee('data-provider-booking-countdown', false)
            ->assertSee($heldBooking->booking_code)
            ->assertSee($rejectedBooking->booking_code)
            ->assertSee($paymentSubmittedBooking->booking_code);
    }

    public function test_customer_can_extend_a_held_booking(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $provider = User::factory()->create(['role' => 'provider']);
        $place = BusinessPlace::factory()->create(['provider_id' => $provider->id]);
        $service = BusinessService::factory()->create(['business_place_id' => $place->id, 'price_per_hour' => 100000]);
        $schedule = ServiceSchedule::factory()->create([
            'business_service_id' => $service->id,
            'start_time' => '08:00',
            'end_time' => '12:00',
        ]);
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'business_service_id' => $service->id,
            'service_schedule_id' => $schedule->id,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'total_cost' => 100000,
            'status' => 'held',
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->actingAs($user)
            ->patch(route('bookings.extend', $booking), ['additional_hours' => 2])
            ->assertRedirect();

        $booking->refresh();
        $this->assertSame('11:00:00', $booking->end_time);
        $this->assertSame('300000.00', (string) $booking->total_cost);
    }

    public function test_customer_can_cancel_a_rejected_booking(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'status' => 'rejected',
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->actingAs($user)
            ->delete(route('bookings.cancel', $booking))
            ->assertRedirect(route('bookings.index'));

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'cancelled',
            'expires_at' => null,
        ]);
    }

    public function test_provider_can_release_a_rejected_booking_hold(): void
    {
        $provider = User::factory()->create(['role' => 'provider']);
        $customer = User::factory()->create(['role' => 'user']);
        $place = BusinessPlace::factory()->create(['provider_id' => $provider->id]);
        $service = BusinessService::factory()->create(['business_place_id' => $place->id]);
        $schedule = ServiceSchedule::factory()->create([
            'business_service_id' => $service->id,
            'day_of_week' => Carbon::MONDAY,
        ]);
        $bookingDate = today()->next(Carbon::MONDAY);
        $booking = Booking::factory()->create([
            'user_id' => $customer->id,
            'business_service_id' => $service->id,
            'service_schedule_id' => $schedule->id,
            'booking_date' => $bookingDate,
            'status' => 'rejected',
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->actingAs($provider)
            ->patch(route('provider.bookings.release', $booking))
            ->assertRedirect();

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'expired',
        ]);

        $this->actingAs($customer)
            ->post(route('bookings.store', $place), [
                'service_schedule_id' => $schedule->id,
                'booking_date' => $bookingDate->toDateString(),
                'start_time' => '08:00',
                'end_time' => '09:00',
            ])
            ->assertRedirect();
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
            ->assertSee('booking-calendar-booking-layout', false)
            ->assertSee('booking-explore-section', false)
            ->assertSee('data-calendar-booking', false)
            ->assertSee('data-booking-detail', false)
            ->assertSee('Siap menemukan jadwal yang cocok?')
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
            ->assertSee('booking-search')
            ->assertSee('booking-all-status-panel', false)
            ->assertDontSee('booking-calendar-booking-layout', false);
    }

    public function test_booking_calendar_only_displays_held_and_confirmed_bookings(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $provider = User::factory()->create(['role' => 'provider']);
        $place = BusinessPlace::factory()->create(['provider_id' => $provider->id]);
        $service = BusinessService::factory()->create(['business_place_id' => $place->id]);
        $schedule = ServiceSchedule::factory()->create(['business_service_id' => $service->id]);
        $bookingDate = today()->addDays(3);

        $heldBooking = Booking::factory()->create([
            'user_id' => $user->id,
            'business_service_id' => $service->id,
            'service_schedule_id' => $schedule->id,
            'booking_date' => $bookingDate,
            'booking_code' => 'BK-CALENDAR-HELD',
            'status' => 'held',
            'expires_at' => now()->addMinutes(10),
        ]);
        $confirmedBooking = $heldBooking->replicate(['booking_code']);
        $confirmedBooking->booking_code = 'BK-CALENDAR-CONFIRMED';
        $confirmedBooking->status = 'confirmed';
        $confirmedBooking->expires_at = null;
        $confirmedBooking->save();
        $rejectedBooking = $heldBooking->replicate(['booking_code']);
        $rejectedBooking->booking_code = 'BK-CALENDAR-REJECTED';
        $rejectedBooking->status = 'rejected';
        $rejectedBooking->save();
        $cancelledBooking = $heldBooking->replicate(['booking_code']);
        $cancelledBooking->booking_code = 'BK-CALENDAR-CANCELLED';
        $cancelledBooking->status = 'cancelled';
        $cancelledBooking->expires_at = null;
        $cancelledBooking->save();

        $this->actingAs($user)
            ->get(route('bookings.index', ['month' => $bookingDate->format('Y-m')]))
            ->assertOk()
            ->assertSee($heldBooking->booking_code)
            ->assertSee($confirmedBooking->booking_code)
            ->assertViewHas('calendarBookings', function ($calendarBookings) use ($heldBooking, $confirmedBooking, $rejectedBooking, $cancelledBooking): bool {
                return $calendarBookings->pluck('id')->contains($heldBooking->id)
                    && $calendarBookings->pluck('id')->contains($confirmedBooking->id)
                    && ! $calendarBookings->pluck('id')->contains($rejectedBooking->id)
                    && ! $calendarBookings->pluck('id')->contains($cancelledBooking->id);
            });
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
