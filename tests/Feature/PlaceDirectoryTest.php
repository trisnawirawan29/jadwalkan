<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BusinessCategory;
use App\Models\BusinessPlace;
use App\Models\BusinessService;
use App\Models\ServiceSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PlaceDirectoryTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_public_directory_displays_active_places_and_filter_options(): void
    {
        $provider = User::factory()->create(['role' => 'provider']);
        $category = BusinessCategory::query()->where('name', 'Olahraga')->firstOrFail();
        $place = BusinessPlace::factory()->create([
            'provider_id' => $provider->id,
            'name' => 'Nusantara Place',
            'province_code' => '51',
            'province_name' => 'Bali',
        ]);
        $place->businessCategories()->attach($category);
        BusinessPlace::factory()->create(['provider_id' => $provider->id, 'name' => 'Inactive Place', 'is_active' => false]);

        $response = $this->get(route('places.index'));

        $response->assertOk()
            ->assertViewIs('places.index')
            ->assertSee('Nusantara Place')
            ->assertSee('Bali')
            ->assertSee('Olahraga')
            ->assertDontSee('Inactive Place');
    }

    public function test_directory_filters_places_by_category_and_province(): void
    {
        $provider = User::factory()->create(['role' => 'provider']);
        $category = BusinessCategory::query()->where('name', 'Olahraga')->firstOrFail();
        $matchingPlace = BusinessPlace::factory()->create(['provider_id' => $provider->id, 'name' => 'Matching Place', 'province_code' => '51']);
        $matchingPlace->businessCategories()->attach($category);
        $otherPlace = BusinessPlace::factory()->create(['provider_id' => $provider->id, 'name' => 'Other Place', 'province_code' => '32']);

        $this->get(route('places.index', ['province' => '51', 'category' => $category->id]))
            ->assertOk()
            ->assertSee('Matching Place')
            ->assertDontSee('Other Place');
    }

    public function test_authenticated_user_profile_region_is_used_for_directory_search(): void
    {
        $provider = User::factory()->create(['role' => 'provider']);
        $user = User::factory()->create([
            'role' => 'user',
            'province_code' => '51',
            'province_name' => 'Bali',
            'regency_code' => '51.01',
            'regency_name' => 'Kabupaten Jembrana',
        ]);
        BusinessPlace::factory()->create(['provider_id' => $provider->id, 'name' => 'Lokasi Sesuai', 'province_code' => '51', 'regency_code' => '51.01']);
        BusinessPlace::factory()->create(['provider_id' => $provider->id, 'name' => 'Lokasi Berbeda', 'province_code' => '32', 'regency_code' => '32.01']);

        $this->actingAs($user)
            ->withSession(['locale' => 'id'])
            ->get(route('places.index'))
            ->assertOk()
            ->assertSee('Lokasi Sesuai')
            ->assertDontSee('Lokasi Berbeda')
            ->assertSee('Menampilkan tempat di sekitar lokasi profil Anda');

        $this->actingAs($user)
            ->get(route('places.index', ['all_locations' => 1]))
            ->assertSee('Lokasi Berbeda');
    }

    public function test_public_place_marks_held_booking_slots_as_unavailable(): void
    {
        $provider = User::factory()->create(['role' => 'provider']);
        $place = BusinessPlace::factory()->create(['provider_id' => $provider->id]);
        $service = BusinessService::factory()->create(['business_place_id' => $place->id]);
        $schedule = ServiceSchedule::factory()->create([
            'business_service_id' => $service->id,
            'day_of_week' => Carbon::MONDAY,
            'start_time' => '08:00',
            'end_time' => '10:00',
        ]);
        Booking::factory()->create([
            'business_service_id' => $service->id,
            'service_schedule_id' => $schedule->id,
            'booking_date' => today()->next(Carbon::MONDAY),
            'start_time' => '08:00',
            'end_time' => '09:00',
            'status' => 'held',
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->actingAs(User::factory()->create(['role' => 'user']))
            ->withSession(['locale' => 'id'])
            ->get(route('places.show', $place))
            ->assertOk()
            ->assertSee('data-bookings')
            ->assertSee('held')
            ->assertSee('Sedang hold')
            ->assertSee('Sudah dibooking');
    }

    public function test_place_booking_url_can_be_opened_after_a_hold_expires(): void
    {
        $provider = User::factory()->create(['role' => 'provider']);
        $place = BusinessPlace::factory()->create(['provider_id' => $provider->id]);

        $this->actingAs(User::factory()->create(['role' => 'user']))
            ->get(route('places.bookings', $place))
            ->assertOk()
            ->assertViewIs('places.show')
            ->assertSee($place->name);
    }

    public function test_every_authenticated_role_sees_a_booking_form_on_a_place_page(): void
    {
        $provider = User::factory()->create(['role' => 'provider']);
        $place = BusinessPlace::factory()->create(['provider_id' => $provider->id]);
        $service = BusinessService::factory()->create(['business_place_id' => $place->id]);
        ServiceSchedule::factory()->create(['business_service_id' => $service->id]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('places.show', $place))
            ->assertOk()
            ->assertSee('name="service_schedule_id"', false);

        $this->actingAs(User::factory()->create(['role' => 'manager']))
            ->get(route('places.show', $place))
            ->assertOk()
            ->assertSee('name="service_schedule_id"', false);
    }

    public function test_guest_is_told_to_login_before_booking(): void
    {
        $provider = User::factory()->create(['role' => 'provider']);
        $place = BusinessPlace::factory()->create(['provider_id' => $provider->id]);
        $service = BusinessService::factory()->create(['business_place_id' => $place->id]);
        ServiceSchedule::factory()->create(['business_service_id' => $service->id]);

        $this->withSession(['locale' => 'id'])
            ->get(route('places.show', $place))
            ->assertOk()
            ->assertSee('Anda harus login dulu untuk melakukan booking.')
            ->assertDontSee('name="service_schedule_id"', false);
    }
}
