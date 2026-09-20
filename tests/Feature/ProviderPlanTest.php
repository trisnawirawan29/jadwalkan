<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BusinessPlace;
use App\Models\BusinessService;
use App\Models\ProviderPlan;
use App\Models\ServiceSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ProviderPlanTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_superadmin_can_create_a_provider_plan(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.provider-plans.store'), [
                'name' => 'Professional',
                'description' => 'Untuk provider berkembang.',
                'max_business_places' => 5,
                'max_services_per_place' => 10,
                'monthly_price' => 499000,
                'max_bookings_per_month' => 300,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.provider-plans.index'));

        $this->assertDatabaseHas('provider_plans', [
            'name' => 'Professional',
            'max_business_places' => 5,
            'max_services_per_place' => 10,
            'max_bookings_per_month' => 300,
        ]);
    }

    public function test_provider_plan_limits_businesses_services_and_monthly_bookings(): void
    {
        $provider = User::factory()->create(['role' => 'provider']);
        $customer = User::factory()->create(['role' => 'user']);
        $plan = ProviderPlan::factory()->create([
            'name' => 'Starter',
            'max_business_places' => 1,
            'max_services_per_place' => 1,
            'max_bookings_per_month' => 1,
        ]);
        $provider->update(['provider_plan_id' => $plan->id]);
        $place = BusinessPlace::factory()->create(['provider_id' => $provider->id]);
        $service = BusinessService::factory()->create(['business_place_id' => $place->id]);
        $schedule = ServiceSchedule::factory()->create(['business_service_id' => $service->id, 'day_of_week' => 1]);
        $bookingDate = today()->next(Carbon::MONDAY);
        Booking::factory()->create([
            'user_id' => $customer->id,
            'business_service_id' => $service->id,
            'service_schedule_id' => $schedule->id,
            'booking_date' => $bookingDate,
            'status' => 'confirmed',
        ]);

        $this->actingAs($provider)
            ->post(route('provider.business-places.store'), ['name' => 'Bisnis Kedua'])
            ->assertSessionHasErrors('name');
        $this->actingAs($provider)
            ->post(route('provider.business-places.services.store', $place), ['name' => 'Layanan Kedua', 'price_per_hour' => 100000])
            ->assertSessionHasErrors('name');
        $this->actingAs($customer)
            ->post(route('bookings.store', $place), [
                'service_schedule_id' => $schedule->id,
                'booking_date' => $bookingDate->toDateString(),
                'start_time' => '10:00',
                'end_time' => '11:00',
            ])
            ->assertSessionHasErrors('booking_date');
    }

    public function test_provider_is_redirected_with_upgrade_warning_when_clicking_add_business_at_limit(): void
    {
        $provider = User::factory()->create(['role' => 'provider']);
        $plan = ProviderPlan::factory()->create(['name' => 'Starter', 'max_business_places' => 1]);
        $provider->update(['provider_plan_id' => $plan->id, 'provider_plan_expires_at' => now()->addMonth()]);
        BusinessPlace::factory()->create(['provider_id' => $provider->id]);

        $this->actingAs($provider)
            ->get(route('provider.business-places.create'))
            ->assertRedirect(route('provider.business-places.index'))
            ->assertSessionHas('warning', 'Paket Starter membatasi hingga 1 bisnis. Anda sudah menggunakan seluruh kuota. Silakan upgrade paket untuk menambahkan bisnis baru.')
            ->assertSessionHas('show_plan_upgrade', true);
    }

    public function test_provider_staff_cannot_manage_provider_plans(): void
    {
        $staff = User::factory()->create(['role' => 'provider_staff']);

        $this->actingAs($staff)
            ->get(route('admin.provider-plans.index'))
            ->assertForbidden();
    }

    public function test_superadmin_can_revoke_provider_plan_and_disable_businesses(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $plan = ProviderPlan::factory()->create();
        $provider = User::factory()->create(['role' => 'provider', 'provider_plan_id' => $plan->id, 'provider_plan_expires_at' => now()->addMonth()]);
        $place = BusinessPlace::factory()->create(['provider_id' => $provider->id, 'is_active' => true]);

        $this->actingAs($admin)
            ->patch(route('admin.users.revoke-provider-plan', $provider))
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $provider->id, 'provider_plan_id' => ProviderPlan::query()->where('is_free', true)->value('id'), 'provider_plan_expires_at' => null]);
        $this->assertDatabaseHas('business_places', ['id' => $place->id, 'is_active' => false]);
    }

    public function test_promoting_a_user_to_provider_assigns_the_free_plan(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $freePlan = ProviderPlan::query()->where('is_free', true)->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.users.role', $user), ['role' => 'provider'])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'provider',
            'provider_plan_id' => $freePlan->id,
        ]);
    }

    public function test_superadmin_can_toggle_provider_plan_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $plan = ProviderPlan::factory()->create(['is_active' => true]);

        $this->actingAs($admin)
            ->patch(route('admin.provider-plans.toggle-status', $plan))
            ->assertRedirect();

        $this->assertDatabaseHas('provider_plans', ['id' => $plan->id, 'is_active' => false]);
    }

    public function test_new_provider_is_assigned_the_free_plan_by_default(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $freePlan = ProviderPlan::query()->where('is_free', true)->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Provider Baru',
                'email' => 'provider-baru@example.com',
                'role' => 'provider',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('admin.users'));

        $this->assertDatabaseHas('users', [
            'email' => 'provider-baru@example.com',
            'role' => 'provider',
            'provider_plan_id' => $freePlan->id,
        ]);
    }
}
