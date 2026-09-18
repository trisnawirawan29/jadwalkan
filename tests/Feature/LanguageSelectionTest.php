<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LanguageSelectionTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_authenticated_user_can_change_language(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('language.update'), [
            'locale' => 'en',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('locale', 'en');

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('>EN<', false);
    }
}
