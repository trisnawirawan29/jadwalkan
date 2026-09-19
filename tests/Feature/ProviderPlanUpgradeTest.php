<?php

namespace Tests\Feature;

use App\Models\BusinessPlace;
use App\Models\ProviderPlan;
use App\Models\ProviderPlanUpgrade;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProviderPlanUpgradeTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_provider_can_submit_upgrade_and_superadmin_can_approve_it(): void
    {
        Storage::fake('public');
        $provider = User::factory()->create(['role' => 'provider']);
        $admin = User::factory()->create(['role' => 'admin']);
        $plan = ProviderPlan::factory()->create(['name' => 'Professional', 'monthly_price' => 499000]);

        $this->actingAs($provider)
            ->post(route('provider.plans.upgrade', $plan), [
                'payment_method' => 'bank_transfer',
                'payment_proof' => UploadedFile::fake()->image('upgrade-proof.jpg'),
            ])
            ->assertRedirect(route('provider.plans.index'));

        $upgrade = ProviderPlanUpgrade::query()->firstOrFail();
        $this->assertSame('pending', $upgrade->status);
        $this->assertFalse($upgrade->is_prorated);
        Storage::disk('public')->assertExists($upgrade->payment_proof);

        $this->actingAs($admin)
            ->patch(route('admin.provider-plan-upgrades.approve', $upgrade))
            ->assertRedirect();

        $this->assertDatabaseHas('provider_plan_upgrades', ['id' => $upgrade->id, 'status' => 'approved', 'reviewed_by' => $admin->id]);
        $this->assertDatabaseHas('users', ['id' => $provider->id, 'provider_plan_id' => $plan->id]);
        $this->assertNotNull($provider->fresh()->provider_plan_started_at);
        $this->assertTrue($provider->fresh()->provider_plan_expires_at->isFuture());
    }

    public function test_provider_cannot_create_multiple_pending_upgrades(): void
    {
        $provider = User::factory()->create(['role' => 'provider']);
        $firstPlan = ProviderPlan::factory()->create(['monthly_price' => 100000]);
        $secondPlan = ProviderPlan::factory()->create(['monthly_price' => 200000]);
        ProviderPlanUpgrade::factory()->create(['provider_id' => $provider->id, 'provider_plan_id' => $firstPlan->id, 'status' => 'pending']);

        $this->actingAs($provider)
            ->post(route('provider.plans.upgrade', $secondPlan), [
                'payment_method' => 'qris',
                'payment_proof' => UploadedFile::fake()->image('upgrade-proof.jpg'),
            ])
            ->assertSessionHasErrors('provider_plan');
    }

    public function test_upgrade_amount_is_prorated_for_an_active_plan(): void
    {
        Storage::fake('public');
        Carbon::setTestNow(Carbon::create(2026, 9, 15, 10));
        $provider = User::factory()->create(['role' => 'provider']);
        $currentPlan = ProviderPlan::factory()->create(['monthly_price' => 300000]);
        $targetPlan = ProviderPlan::factory()->create(['monthly_price' => 600000]);
        $provider->update([
            'provider_plan_id' => $currentPlan->id,
            'provider_plan_started_at' => Carbon::create(2026, 9, 1),
            'provider_plan_expires_at' => Carbon::create(2026, 9, 30, 23, 59, 59),
        ]);

        $this->actingAs($provider)
            ->post(route('provider.plans.upgrade', $targetPlan), [
                'payment_method' => 'bank_transfer',
                'payment_proof' => UploadedFile::fake()->image('upgrade-proof.jpg'),
            ])
            ->assertRedirect(route('provider.plans.index'));

        $this->assertDatabaseHas('provider_plan_upgrades', [
            'provider_id' => $provider->id,
            'provider_plan_id' => $targetPlan->id,
            'amount' => 160000,
            'is_prorated' => true,
            'proration_remaining_days' => 16,
            'proration_total_days' => 30,
        ]);
        Carbon::setTestNow();
    }

    public function test_provider_staff_cannot_submit_plan_upgrade(): void
    {
        $staff = User::factory()->create(['role' => 'provider_staff']);
        $plan = ProviderPlan::factory()->create();

        $this->actingAs($staff)
            ->get(route('provider.plans.index'))
            ->assertForbidden();
    }

    public function test_provider_sees_superadmin_payment_details_for_selected_methods(): void
    {
        $provider = User::factory()->create(['role' => 'provider']);
        ProviderPlan::factory()->create(['monthly_price' => 100000]);
        Setting::updateOrCreate(['key' => 'provider_plan_bank_name'], ['value' => 'Bank Nusantara']);
        Setting::updateOrCreate(['key' => 'provider_plan_bank_account_name'], ['value' => 'Jadwalkan']);
        Setting::updateOrCreate(['key' => 'provider_plan_bank_account_number'], ['value' => '1234567890']);
        Setting::updateOrCreate(['key' => 'provider_plan_qris_image'], ['value' => 'provider-plan-payment/qris.png']);

        $this->actingAs($provider)
            ->get(route('provider.plans.index'))
            ->assertOk()
            ->assertSee('Bank Nusantara')
            ->assertSee('1234567890')
            ->assertSee('/storage/provider-plan-payment/qris.png', false);
    }

    public function test_expired_provider_plan_disables_the_provider_businesses(): void
    {
        $provider = User::factory()->create([
            'role' => 'provider',
            'provider_plan_id' => ProviderPlan::factory(),
            'provider_plan_expires_at' => now()->subMinute(),
        ]);
        $activePlace = BusinessPlace::factory()->create(['provider_id' => $provider->id, 'is_active' => true]);
        $inactivePlace = BusinessPlace::factory()->create(['provider_id' => $provider->id, 'is_active' => false]);

        $this->artisan('provider-plans:deactivate-expired')
            ->expectsOutput('Disabled 1 expired provider business(es).')
            ->assertSuccessful();

        $this->assertDatabaseHas('business_places', ['id' => $activePlace->id, 'is_active' => false]);
        $this->assertDatabaseHas('business_places', ['id' => $inactivePlace->id, 'is_active' => false]);
    }

    public function test_superadmin_can_view_upgrade_financial_report(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.reports.upgrade-financial'))
            ->assertOk()
            ->assertSee('Laporan upgrade paket');
    }
}
