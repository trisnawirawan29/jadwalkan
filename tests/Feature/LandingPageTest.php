<?php

namespace Tests\Feature;

use App\Models\BusinessCategory;
use App\Models\BusinessPlace;
use App\Models\BusinessService;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_landing_page_displays_active_statistics_categories_and_places(): void
    {
        $provider = User::factory()->create(['role' => 'provider']);
        $category = BusinessCategory::query()->where('name', 'Olahraga')->firstOrFail();
        $place = BusinessPlace::factory()->create([
            'provider_id' => $provider->id,
            'name' => 'Cakrawala Arena',
            'latitude' => -8.65,
            'longitude' => 115.21,
        ]);
        $place->businessCategories()->attach($category);
        BusinessService::factory()->create(['business_place_id' => $place->id, 'name' => 'Lapangan Utama']);

        $inactivePlace = BusinessPlace::factory()->create([
            'provider_id' => $provider->id,
            'name' => 'Tempat Tidak Aktif',
            'is_active' => false,
        ]);
        BusinessService::factory()->create(['business_place_id' => $inactivePlace->id, 'is_active' => true]);

        $response = $this->get(route('landing'));

        $response->assertOk()
            ->assertViewIs('welcome')
            ->assertViewHas('activeServiceCount', 1)
            ->assertSee('Cakrawala Arena')
            ->assertSee('Olahraga')
            ->assertDontSee('Tempat Tidak Aktif')
            ->assertSee('-8.65')
            ->assertSee('115.21');
    }

    public function test_authenticated_user_sees_dashboard_call_to_action(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('landing'))
            ->assertOk()
            ->assertSee('Dashboard');
    }
}
