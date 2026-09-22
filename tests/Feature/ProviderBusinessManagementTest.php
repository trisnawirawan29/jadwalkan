<?php

namespace Tests\Feature;

use App\Models\BusinessCategory;
use App\Models\BusinessPlace;
use App\Models\BusinessService;
use App\Models\ProviderPaymentMethod;
use App\Models\ServiceClosure;
use App\Models\ServiceSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProviderBusinessManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_provider_can_create_a_business_place_service_and_recurring_schedule(): void
    {
        $provider = User::factory()->create(['role' => 'provider']);
        $category = BusinessCategory::query()->where('name', 'Bulutangkis')->firstOrFail();
        $parentCategory = BusinessCategory::query()->where('name', 'Olahraga')->firstOrFail();

        $placeResponse = $this->actingAs($provider)->post(route('provider.business-places.store'), [
            'name' => 'Arena Sport Center',
            'business_category_ids' => [$parentCategory->id, $category->id],
            'address' => 'Jl. Olahraga No. 1',
            'google_maps_url' => 'https://www.google.com/maps/search/Arena+Sport+Center',
            'latitude' => '-8.6705',
            'longitude' => '115.2126',
            'province_code' => '51',
            'province_name' => 'Bali',
            'regency_code' => '51.03',
            'regency_name' => 'Kabupaten Badung',
            'district_code' => '51.03.01',
            'district_name' => 'Kuta',
            'hold_duration_minutes' => 25,
            'is_active' => 1,
        ]);
        $businessPlace = BusinessPlace::query()->firstOrFail();

        $serviceResponse = $this->actingAs($provider)->post(route('provider.business-places.services.store', $businessPlace), [
            'name' => 'Lapangan Futsal A',
            'business_category_id' => $category->id,
            'type' => 'Futsal',
            'price_per_hour' => 150000,
            'hourly_prices' => ['6' => ['08:00' => 175000]],
            'is_active' => 1,
        ]);
        $businessService = BusinessService::query()->firstOrFail();

        $scheduleResponse = $this->actingAs($provider)->post(route('provider.business-places.services.schedules.store', [$businessPlace, $businessService]), [
            'day_of_week' => 6,
            'start_time' => '08:00',
            'end_time' => '10:00',
            'is_active' => 1,
        ]);

        $placeResponse->assertRedirect(route('provider.business-places.index'));
        $serviceResponse->assertRedirect(route('provider.business-places.services.index', $businessPlace));
        $scheduleResponse->assertRedirect(route('provider.business-places.services.schedules.index', [$businessPlace, $businessService]));
        $this->assertDatabaseHas('business_places', ['id' => $businessPlace->id, 'provider_id' => $provider->id, 'name' => 'Arena Sport Center', 'hold_duration_minutes' => 25, 'latitude' => '-8.6705000', 'longitude' => '115.2126000', 'province_code' => '51', 'regency_code' => '51.03', 'district_code' => '51.03.01']);
        $this->assertDatabaseHas('business_place_business_category', ['business_place_id' => $businessPlace->id, 'business_category_id' => $category->id]);
        $this->assertDatabaseHas('business_place_business_category', ['business_place_id' => $businessPlace->id, 'business_category_id' => $parentCategory->id]);
        $this->assertDatabaseHas('business_services', ['id' => $businessService->id, 'business_place_id' => $businessPlace->id, 'business_category_id' => $category->id, 'name' => 'Lapangan Futsal A']);
        $this->assertSame(175000.0, (float) $businessService->hourlyPrices()->where('day_of_week', 6)->whereTime('start_time', '08:00:00')->value('price'));
        $this->assertDatabaseHas('service_schedules', ['business_service_id' => $businessService->id, 'day_of_week' => 6, 'start_time' => '08:00', 'end_time' => '10:00']);
    }

    public function test_provider_dashboard_only_shows_the_logged_in_providers_data(): void
    {
        $provider = User::factory()->create(['role' => 'provider', 'name' => 'Provider Saya']);
        $otherProvider = User::factory()->create(['role' => 'provider']);
        $ownPlace = BusinessPlace::factory()->create(['provider_id' => $provider->id, 'name' => 'Arena Milik Saya']);
        BusinessPlace::factory()->create(['provider_id' => $otherProvider->id, 'name' => 'Arena Provider Lain']);
        BusinessService::factory()->create(['business_place_id' => $ownPlace->id]);

        $this->actingAs($provider)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewIs('dashboard.provider')
            ->assertSee('Arena Milik Saya')
            ->assertSee('Booking saya')
            ->assertDontSee('Arena Provider Lain')
            ->assertViewHas('stats', fn ($stats) => $stats[0]['value'] === 1 && $stats[1]['value'] === 1);
    }

    public function test_provider_can_create_one_schedule_for_multiple_days(): void
    {
        $provider = User::factory()->create(['role' => 'provider']);
        $businessPlace = BusinessPlace::factory()->create(['provider_id' => $provider->id]);
        $businessService = BusinessService::factory()->create(['business_place_id' => $businessPlace->id]);

        $response = $this->actingAs($provider)->post(route('provider.business-places.services.schedules.store', [$businessPlace, $businessService]), [
            'day_of_week' => [1, 3, 5],
            'start_time' => '08:00',
            'end_time' => '10:00',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('provider.business-places.services.schedules.index', [$businessPlace, $businessService]));
        $this->assertDatabaseCount('service_schedules', 3);
        foreach ([1, 3, 5] as $day) {
            $this->assertDatabaseHas('service_schedules', ['business_service_id' => $businessService->id, 'day_of_week' => $day, 'start_time' => '08:00', 'end_time' => '10:00']);
        }
    }

    public function test_provider_can_configure_bank_and_qris_payment_methods(): void
    {
        Storage::fake('public');
        $provider = User::factory()->create(['role' => 'provider']);

        $this->actingAs($provider)
            ->get(route('provider.payment'))
            ->assertOk()
            ->assertSee('Bank Central Asia (BCA)');

        $this->actingAs($provider)
            ->post(route('provider.payment-methods.store'), [
                'type' => 'bank_transfer',
                'bank_name' => 'Bank Central Asia (BCA)',
                'account_name' => 'Provider Saya',
                'account_number' => '1234567890',
            ])
            ->assertRedirect();

        $this->actingAs($provider)
            ->post(route('provider.payment-methods.store'), [
                'type' => 'qris',
                'qris_image' => UploadedFile::fake()->image('qris.png'),
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('provider_payment_methods', 2);
        $bank = ProviderPaymentMethod::query()->where('provider_id', $provider->id)->where('type', 'bank_transfer')->firstOrFail();
        $this->actingAs($provider)
            ->patch(route('provider.payment-methods.toggle', $bank))
            ->assertRedirect();
        $this->assertDatabaseHas('provider_payment_methods', ['id' => $bank->id, 'is_active' => false]);

        $qris = ProviderPaymentMethod::query()->where('provider_id', $provider->id)->where('type', 'qris')->firstOrFail();
        Storage::disk('public')->assertExists($qris->qris_image);
    }

    public function test_new_business_form_warns_when_provider_has_no_payment_method(): void
    {
        $provider = User::factory()->create(['role' => 'provider']);

        $this->actingAs($provider)
            ->withSession(['locale' => 'id'])
            ->get(route('provider.business-places.create'))
            ->assertOk()
            ->assertSee('Sistem pembayaran belum diatur.')
            ->assertSee(route('provider.payment'))
            ->assertDontSee('Buat tempat bisnis')
            ->assertDontSee('business-place-form-grid');
    }

    public function test_business_form_only_uses_payment_methods_owned_by_provider(): void
    {
        $provider = User::factory()->create(['role' => 'provider']);
        $otherProvider = User::factory()->create(['role' => 'provider']);
        $ownMethod = ProviderPaymentMethod::query()->create([
            'provider_id' => $provider->id,
            'type' => 'bank_transfer',
            'bank_name' => 'Bank Milik Saya',
            'account_name' => 'Provider Saya',
            'account_number' => '1111111111',
        ]);
        $inactiveMethod = ProviderPaymentMethod::query()->create([
            'provider_id' => $provider->id,
            'type' => 'bank_transfer',
            'bank_name' => 'Bank Nonaktif',
            'account_name' => 'Provider Saya',
            'account_number' => '2222222222',
            'is_active' => false,
        ]);
        $otherMethod = ProviderPaymentMethod::query()->create([
            'provider_id' => $otherProvider->id,
            'type' => 'bank_transfer',
            'bank_name' => 'Bank Provider Lain',
            'account_name' => 'Provider Lain',
            'account_number' => '9999999999',
        ]);

        $this->actingAs($provider)
            ->get(route('provider.business-places.create'))
            ->assertOk()
            ->assertSee($ownMethod->account_number)
            ->assertDontSee($inactiveMethod->account_number)
            ->assertDontSee($otherMethod->account_number);

        $response = $this->actingAs($provider)->post(route('provider.business-places.store'), [
            'name' => 'Bisnis Provider Saya',
            'payment_method_ids' => [$otherMethod->id],
        ]);

        $response->assertSessionHasErrors('payment_method_ids.0');
        $this->assertDatabaseMissing('business_places', ['name' => 'Bisnis Provider Saya']);
    }

    public function test_provider_can_upload_replace_and_delete_a_business_place_cover_image(): void
    {
        Storage::fake('public');
        $provider = User::factory()->create(['role' => 'provider']);
        $businessPlace = BusinessPlace::factory()->create(['provider_id' => $provider->id]);

        $this->actingAs($provider)->put(route('provider.business-places.update', $businessPlace), [
            'name' => $businessPlace->name,
            'hold_duration_minutes' => $businessPlace->hold_duration_minutes,
            'cover_image' => UploadedFile::fake()->image('first-cover.jpg'),
        ])->assertRedirect(route('provider.business-places.index'));
        $businessPlace->refresh();
        $firstCover = $businessPlace->cover_image;
        Storage::disk('public')->assertExists($firstCover);

        $this->actingAs($provider)->put(route('provider.business-places.update', $businessPlace), [
            'name' => $businessPlace->name,
            'hold_duration_minutes' => $businessPlace->hold_duration_minutes,
            'cover_image' => UploadedFile::fake()->image('second-cover.png'),
        ])->assertRedirect(route('provider.business-places.index'));
        $businessPlace->refresh();
        $secondCover = $businessPlace->cover_image;
        Storage::disk('public')->assertMissing($firstCover);
        Storage::disk('public')->assertExists($secondCover);

        $this->actingAs($provider)->delete(route('provider.business-places.destroy', $businessPlace))->assertRedirect();
        Storage::disk('public')->assertMissing($secondCover);
    }

    public function test_provider_can_upload_replace_and_delete_a_service_cover_image(): void
    {
        Storage::fake('public');
        $provider = User::factory()->create(['role' => 'provider']);
        $businessPlace = BusinessPlace::factory()->create(['provider_id' => $provider->id]);
        $businessService = BusinessService::factory()->create(['business_place_id' => $businessPlace->id]);

        $this->actingAs($provider)->put(route('provider.business-places.services.update', [$businessPlace, $businessService]), [
            'name' => $businessService->name,
            'price_per_hour' => $businessService->price_per_hour,
            'cover_image' => UploadedFile::fake()->image('first-service.jpg'),
        ])->assertRedirect(route('provider.business-places.services.index', $businessPlace));
        $businessService->refresh();
        $firstCover = $businessService->cover_image;
        Storage::disk('public')->assertExists($firstCover);

        $this->actingAs($provider)->put(route('provider.business-places.services.update', [$businessPlace, $businessService]), [
            'name' => $businessService->name,
            'price_per_hour' => $businessService->price_per_hour,
            'cover_image' => UploadedFile::fake()->image('second-service.png'),
        ])->assertRedirect(route('provider.business-places.services.index', $businessPlace));
        $businessService->refresh();
        $secondCover = $businessService->cover_image;
        Storage::disk('public')->assertMissing($firstCover);
        Storage::disk('public')->assertExists($secondCover);

        $this->actingAs($provider)->delete(route('provider.business-places.services.destroy', [$businessPlace, $businessService]))->assertRedirect();
        Storage::disk('public')->assertMissing($secondCover);
    }

    public function test_provider_can_toggle_business_place_status(): void
    {
        $provider = User::factory()->create(['role' => 'provider']);
        $businessPlace = BusinessPlace::factory()->create(['provider_id' => $provider->id, 'is_active' => true]);

        $this->actingAs($provider)->patch(route('provider.business-places.status', $businessPlace))->assertRedirect();
        $this->assertDatabaseHas('business_places', ['id' => $businessPlace->id, 'is_active' => false]);

        $this->actingAs($provider)->patch(route('provider.business-places.status', $businessPlace))->assertRedirect();
        $this->assertDatabaseHas('business_places', ['id' => $businessPlace->id, 'is_active' => true]);
    }

    public function test_provider_can_load_indonesia_regions_through_the_application_proxy(): void
    {
        Http::fake([
            'https://wilayah.id/api/*' => Http::response(['data' => [['code' => '51', 'name' => 'Bali']]], 200),
        ]);
        $provider = User::factory()->create(['role' => 'provider']);

        $this->actingAs($provider)
            ->get(route('provider.regions', ['level' => 'provinces']))
            ->assertOk()
            ->assertJsonPath('data.0.code', '51')
            ->assertJsonPath('data.0.name', 'Bali');
    }

    public function test_schedule_form_uses_the_selected_language_for_day_names(): void
    {
        $provider = User::factory()->create(['role' => 'provider']);
        $businessPlace = BusinessPlace::factory()->create(['provider_id' => $provider->id]);
        $businessService = BusinessService::factory()->create(['business_place_id' => $businessPlace->id]);

        $this->actingAs($provider)
            ->withSession(['locale' => 'en'])
            ->get(route('provider.business-places.services.schedules.create', [$businessPlace, $businessService]))
            ->assertOk()
            ->assertSee('Monday');
    }

    public function test_provider_can_geocode_a_business_location_through_the_application_proxy(): void
    {
        Http::fake([
            'https://nominatim.openstreetmap.org/search*' => Http::response([['lat' => '-8.6705', 'lon' => '115.2126']], 200),
        ]);
        $provider = User::factory()->create(['role' => 'provider']);

        $this->actingAs($provider)
            ->get(route('provider.map.geocode', ['q' => 'Denpasar, Bali, Indonesia']))
            ->assertOk()
            ->assertJson(['latitude' => -8.6705, 'longitude' => 115.2126]);
    }

    public function test_provider_cannot_create_overlapping_schedule_for_a_service(): void
    {
        $provider = User::factory()->create(['role' => 'provider']);
        $businessPlace = BusinessPlace::factory()->create(['provider_id' => $provider->id]);
        $businessService = BusinessService::factory()->create(['business_place_id' => $businessPlace->id]);
        ServiceSchedule::factory()->create(['business_service_id' => $businessService->id, 'day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '10:00']);

        $response = $this->actingAs($provider)->post(route('provider.business-places.services.schedules.store', [$businessPlace, $businessService]), [
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '11:00',
            'is_active' => 1,
        ]);

        $response->assertSessionHasErrors(['start_time']);
        $this->assertDatabaseCount('service_schedules', 1);
    }

    public function test_provider_can_mark_a_recurring_service_day_as_closed(): void
    {
        $provider = User::factory()->create(['role' => 'provider']);
        $businessPlace = BusinessPlace::factory()->create(['provider_id' => $provider->id]);
        $businessService = BusinessService::factory()->create(['business_place_id' => $businessPlace->id]);

        $response = $this->actingAs($provider)->post(route('provider.business-places.services.schedules.store', [$businessPlace, $businessService]), [
            'day_of_week' => 7,
            'is_closed' => 1,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('provider.business-places.services.schedules.index', [$businessPlace, $businessService]));
        $this->assertDatabaseHas('service_schedules', [
            'business_service_id' => $businessService->id,
            'day_of_week' => 7,
            'is_closed' => true,
            'start_time' => null,
            'end_time' => null,
        ]);
    }

    public function test_provider_can_add_and_remove_a_future_service_closure_date(): void
    {
        $provider = User::factory()->create(['role' => 'provider']);
        $businessPlace = BusinessPlace::factory()->create(['provider_id' => $provider->id]);
        $businessService = BusinessService::factory()->create(['business_place_id' => $businessPlace->id]);
        $closureDate = now()->addDays(14)->toDateString();

        $this->actingAs($provider)->post(route('provider.business-places.services.closures.store', [$businessPlace, $businessService]), [
            'closure_date' => $closureDate,
            'note' => 'Perawatan lapangan',
        ])->assertRedirect();

        $closure = ServiceClosure::query()->firstOrFail();
        $this->assertDatabaseHas('service_closures', [
            'id' => $closure->id,
            'business_service_id' => $businessService->id,
            'closure_date' => $closureDate.' 00:00:00',
            'note' => 'Perawatan lapangan',
        ]);

        $this->actingAs($provider)->post(route('provider.business-places.services.closures.store', [$businessPlace, $businessService]), [
            'closure_date' => $closureDate,
        ])->assertSessionHasErrors('closure_date');

        $this->actingAs($provider)->delete(route('provider.business-places.services.closures.destroy', [$businessPlace, $businessService, $closure]))->assertRedirect();
        $this->assertDatabaseMissing('service_closures', ['id' => $closure->id]);
    }

    public function test_regular_user_is_forbidden_from_provider_management(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get(route('provider.business-places.index'))->assertForbidden();
    }

    public function test_admin_can_create_a_business_category_with_a_subcategory(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $parent = BusinessCategory::query()->where('name', 'Olahraga')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.business-categories.store'), [
            'name' => 'Panahan',
            'parent_id' => $parent->id,
            'is_active' => 1,
        ])->assertRedirect(route('admin.business-categories.index'));

        $this->assertDatabaseHas('business_categories', ['name' => 'Panahan', 'parent_id' => $parent->id, 'is_active' => true]);
    }

    public function test_registration_creates_a_regular_user_even_when_provider_role_is_submitted(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Penyedia Arena',
            'email' => 'provider@example.com',
            'role' => 'provider',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'provider@example.com', 'role' => 'user']);
    }

    public function test_provider_cannot_manage_another_providers_business_place(): void
    {
        $provider = User::factory()->create(['role' => 'provider']);
        $otherProvider = User::factory()->create(['role' => 'provider']);
        $businessPlace = BusinessPlace::factory()->create(['provider_id' => $otherProvider->id]);

        $this->actingAs($provider)->get(route('provider.business-places.edit', $businessPlace))->assertForbidden();
    }
}
