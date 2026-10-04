<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BusinessPlace;
use App\Models\BusinessService;
use App\Models\ServiceSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class NearbyAvailablePlaceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_returns_nearby_places_with_a_free_service_right_now(): void
    {
        $this->travelTo(today()->setTime(10, 0));

        $provider = User::factory()->create(['role' => 'provider']);
        $place = BusinessPlace::factory()->create([
            'provider_id' => $provider->id,
            'name' => 'Venue Terdekat',
            'latitude' => -8.65,
            'longitude' => 115.21,
        ]);
        $service = BusinessService::factory()->create(['business_place_id' => $place->id, 'name' => 'Lapangan Kosong']);
        ServiceSchedule::factory()->create([
            'business_service_id' => $service->id,
            'day_of_week' => now()->isoWeekday(),
            'start_time' => '00:00',
            'end_time' => '23:59',
        ]);

        $this->getJson(route('nearby-available-places', ['latitude' => -8.651, 'longitude' => 115.211]))
            ->assertOk()
            ->assertJsonPath('places.0.name', 'Venue Terdekat')
            ->assertJsonPath('places.0.services.0', 'Lapangan Kosong');
    }

    public function test_it_excludes_a_place_when_its_schedule_is_full_for_today(): void
    {
        $this->travelTo(today()->setTime(10, 0));

        $provider = User::factory()->create(['role' => 'provider']);
        $place = BusinessPlace::factory()->create(['provider_id' => $provider->id, 'latitude' => -8.65, 'longitude' => 115.21]);
        $service = BusinessService::factory()->create(['business_place_id' => $place->id]);
        $schedule = ServiceSchedule::factory()->create([
            'business_service_id' => $service->id,
            'day_of_week' => now()->isoWeekday(),
            'start_time' => '00:00',
            'end_time' => '23:59',
        ]);
        Booking::factory()->create([
            'business_service_id' => $service->id,
            'service_schedule_id' => $schedule->id,
            'booking_date' => today(),
            'start_time' => '00:00',
            'end_time' => '23:59',
            'status' => 'confirmed',
            'expires_at' => null,
        ]);

        $this->getJson(route('nearby-available-places', ['latitude' => -8.65, 'longitude' => 115.21]))
            ->assertOk()
            ->assertJsonCount(0, 'places');
    }

    public function test_it_excludes_a_place_when_all_of_its_services_are_fully_booked_now(): void
    {
        $this->travelTo(today()->setTime(10, 0));

        $provider = User::factory()->create(['role' => 'provider']);
        $place = BusinessPlace::factory()->create(['provider_id' => $provider->id, 'latitude' => -8.65, 'longitude' => 115.21]);

        foreach (['Lapangan A', 'Lapangan B'] as $serviceName) {
            $service = BusinessService::factory()->create(['business_place_id' => $place->id, 'name' => $serviceName]);
            $schedule = ServiceSchedule::factory()->create([
                'business_service_id' => $service->id,
                'day_of_week' => now()->isoWeekday(),
                'start_time' => '00:00',
                'end_time' => '23:59',
            ]);
            Booking::factory()->create([
                'business_service_id' => $service->id,
                'service_schedule_id' => $schedule->id,
                'booking_date' => today(),
                'start_time' => '00:00',
                'end_time' => '23:59',
                'status' => 'confirmed',
                'expires_at' => null,
            ]);
        }

        $this->getJson(route('nearby-available-places', ['latitude' => -8.65, 'longitude' => 115.21]))
            ->assertOk()
            ->assertJsonCount(0, 'places');
    }

    public function test_it_ignores_past_slots_but_keeps_a_place_with_a_future_free_slot(): void
    {
        $this->travelTo(today()->setTime(10, 0));

        $provider = User::factory()->create(['role' => 'provider']);
        $place = BusinessPlace::factory()->create(['provider_id' => $provider->id, 'latitude' => -8.65, 'longitude' => 115.21]);
        $service = BusinessService::factory()->create(['business_place_id' => $place->id, 'name' => 'Slot Sore']);
        $schedule = ServiceSchedule::factory()->create([
            'business_service_id' => $service->id,
            'day_of_week' => now()->isoWeekday(),
            'start_time' => '08:00',
            'end_time' => '13:00',
        ]);
        Booking::factory()->create([
            'business_service_id' => $service->id,
            'service_schedule_id' => $schedule->id,
            'booking_date' => today(),
            'start_time' => '08:00',
            'end_time' => '10:00',
            'status' => 'confirmed',
            'expires_at' => null,
        ]);

        $this->getJson(route('nearby-available-places', ['latitude' => -8.65, 'longitude' => 115.21]))
            ->assertOk()
            ->assertJsonPath('places.0.name', $place->name);
    }
}
