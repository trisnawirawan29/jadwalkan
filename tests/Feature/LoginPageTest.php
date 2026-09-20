<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LoginPageTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_login_page_uses_application_settings(): void
    {
        Setting::query()->upsert([
            ['key' => 'app_name', 'value' => 'JadwalKita'],
            ['key' => 'app_tagline', 'value' => 'Atur jadwal tanpa ribet'],
            ['key' => 'footer_text', 'value' => 'Hak cipta JadwalKita'],
            ['key' => 'app_version', 'value' => '2.4.0'],
            ['key' => 'primary_color', 'value' => '#123456'],
            ['key' => 'default_theme', 'value' => 'dark'],
        ], ['key'], ['value']);

        $response = $this->get(route('login'));

        $response->assertOk()
            ->assertSee('<title>Login · JadwalKita</title>', false)
            ->assertSee('Atur jadwal tanpa ribet')
            ->assertSee('Hak cipta JadwalKita · v2.4.0')
            ->assertSee('--brand-color: #123456', false)
            ->assertSee('data-bs-theme="dark"', false);
    }
}
