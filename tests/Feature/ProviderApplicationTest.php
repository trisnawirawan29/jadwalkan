<?php

namespace Tests\Feature;

use App\Models\ProviderApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ProviderApplicationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_registration_always_creates_a_regular_user(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Calon Provider',
            'email' => 'calon@example.com',
            'role' => 'provider',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('users', ['email' => 'calon@example.com', 'role' => 'user']);
    }

    public function test_user_can_submit_provider_application_once_while_pending(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->post(route('provider-application.store'), [
            'business_name' => 'Arena Nusantara',
            'business_address' => 'Jl. Merdeka No. 1',
            'phone' => '08123456789',
        ]);

        $response->assertRedirect(route('provider-application.create'));
        $this->assertDatabaseHas('provider_applications', [
            'user_id' => $user->id,
            'business_name' => 'Arena Nusantara',
            'status' => 'pending',
        ]);

        $this->actingAs($user)->post(route('provider-application.store'), [
            'business_name' => 'Arena Kedua',
            'business_address' => 'Jl. Lainnya No. 2',
        ])->assertSessionHasErrors('application');
    }

    public function test_user_dashboard_shows_account_summary_and_provider_application_status(): void
    {
        $user = User::factory()->create(['role' => 'user', 'phone' => '08123456789', 'location' => 'Denpasar']);
        ProviderApplication::create([
            'user_id' => $user->id,
            'business_name' => 'Arena Nusantara',
            'business_address' => 'Jl. Merdeka No. 1',
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewIs('dashboard.user')
            ->assertSee('Informasi akun')
            ->assertSee('Arena Nusantara')
            ->assertSee('Sedang ditinjau')
            ->assertSee('Pengajuan provider');
    }

    public function test_user_can_save_region_preferences_on_profile(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'province_code' => '51',
            'province_name' => 'Bali',
            'regency_code' => '51.01',
            'regency_name' => 'Kabupaten Jembrana',
            'district_code' => '51.01.01',
            'district_name' => 'Negara',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'province_code' => '51',
            'regency_code' => '51.01',
            'district_code' => '51.01.01',
        ]);
    }

    public function test_superadmin_can_approve_application_and_promote_user_to_provider(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $admin = User::factory()->create(['role' => 'admin']);
        $application = ProviderApplication::create([
            'user_id' => $user->id,
            'business_name' => 'Arena Nusantara',
            'business_address' => 'Jl. Merdeka No. 1',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.provider-applications.approve', $application));

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'provider']);
        $this->assertDatabaseHas('provider_applications', ['id' => $application->id, 'status' => 'approved', 'reviewed_by' => $admin->id]);
    }

    public function test_superadmin_can_reject_application_with_a_reason(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $admin = User::factory()->create(['role' => 'admin']);
        $application = ProviderApplication::create([
            'user_id' => $user->id,
            'business_name' => 'Arena Nusantara',
            'business_address' => 'Jl. Merdeka No. 1',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.provider-applications.reject', $application), ['rejection_reason' => 'Data bisnis belum dapat diverifikasi.']);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'user']);
        $this->assertDatabaseHas('provider_applications', ['id' => $application->id, 'status' => 'rejected', 'rejection_reason' => 'Data bisnis belum dapat diverifikasi.']);
    }

    public function test_regular_user_cannot_validate_provider_application(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $application = ProviderApplication::create([
            'user_id' => $user->id,
            'business_name' => 'Arena Nusantara',
            'business_address' => 'Jl. Merdeka No. 1',
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->patch(route('admin.provider-applications.approve', $application))
            ->assertForbidden();

        $this->assertDatabaseHas('provider_applications', ['id' => $application->id, 'status' => 'pending']);
    }
}
