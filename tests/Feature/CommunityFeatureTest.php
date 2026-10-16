<?php

namespace Tests\Feature;

use App\Models\BusinessCategory;
use App\Models\BusinessPlace;
use App\Models\BusinessService;
use App\Models\ServiceSchedule;
use App\Models\SportGroup;
use App\Models\SportPreference;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CommunityFeatureTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_user_can_join_a_sport_community_and_save_interest(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $sport = BusinessCategory::query()->where('slug', 'futsal')->firstOrFail();

        $this->actingAs($user)->post(route('communities.join', $sport))->assertRedirect();

        $this->assertDatabaseHas('user_sport_interest', ['user_id' => $user->id, 'business_category_id' => $sport->id]);
        $this->assertDatabaseHas('community_user', ['user_id' => $user->id, 'status' => 'active']);
    }

    public function test_user_can_manage_sport_preferences_from_profile_agenda(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $sport = BusinessCategory::query()->where('slug', 'futsal')->firstOrFail();

        $this->actingAs($user)->post(route('sport-preferences.store'), ['business_category_id' => $sport->id, 'level' => 'intermediate', 'day_of_week' => 6, 'start_time' => '09:00', 'end_time' => '11:00'])->assertRedirect();

        $preference = SportPreference::query()->where('user_id', $user->id)->firstOrFail();
        $this->actingAs($user)->get(route('profile'))->assertOk()->assertSee('Agenda olahraga saya')->assertSee('Sabtu')->assertSee('Menengah');
        $this->actingAs($user)->delete(route('sport-preferences.destroy', $preference))->assertRedirect();
        $this->assertDatabaseMissing('sport_preferences', ['id' => $preference->id]);
    }

    public function test_user_can_see_popular_nearby_sport_schedules(): void
    {
        $user = User::factory()->create(['role' => 'user', 'latitude' => -8.65, 'longitude' => 115.21]);
        $nearbyUsers = User::factory()->count(2)->create(['role' => 'user', 'latitude' => -8.66, 'longitude' => 115.22]);
        $farUser = User::factory()->create(['role' => 'user', 'latitude' => -7.25, 'longitude' => 112.75]);
        $sport = BusinessCategory::query()->where('slug', 'futsal')->firstOrFail();
        $otherSport = BusinessCategory::query()->where('slug', 'basket')->firstOrFail();
        $preference = ['business_category_id' => $sport->id, 'level' => 'beginner', 'day_of_week' => 5, 'start_time' => '18:00', 'end_time' => '20:00'];

        foreach ($nearbyUsers->push($farUser) as $nearbyUser) {
            SportPreference::factory()->create([...$preference, 'user_id' => $nearbyUser->id]);
        }
        SportPreference::factory()->create([...$preference, 'business_category_id' => $otherSport->id, 'user_id' => $nearbyUsers->first()->id]);

        $this->actingAs($user)->get(route('sport-groups.index'))
            ->assertOk()
            ->assertSee('Jadwal populer di sekitar Anda')
            ->assertSee('popularSchedulesChart')
            ->assertSee('Futsal')
            ->assertSee('Pemula')
            ->assertSee('2 user')
            ->assertSee('Jumat');

        $this->actingAs($user)->get(route('sport-groups.index', ['sport' => $otherSport->id]))
            ->assertOk()
            ->assertSee('<strong>Basket</strong>', false)
            ->assertDontSee('<strong>Futsal</strong>', false);
    }

    public function test_matching_user_sees_group_suggestion_and_can_join(): void
    {
        $creator = User::factory()->create(['role' => 'user', 'latitude' => -8.65, 'longitude' => 115.21]);
        $matchingUser = User::factory()->create(['role' => 'user']);
        $differentLevel = User::factory()->create(['role' => 'user']);
        $sport = BusinessCategory::query()->where('slug', 'futsal')->firstOrFail();
        $activityDate = today()->addDays(7);
        SportPreference::factory()->create(['user_id' => $matchingUser->id, 'business_category_id' => $sport->id, 'level' => 'beginner', 'day_of_week' => $activityDate->isoWeekday(), 'start_time' => '18:00', 'end_time' => '20:00']);
        SportPreference::factory()->create(['user_id' => $differentLevel->id, 'business_category_id' => $sport->id, 'level' => 'advanced', 'day_of_week' => $activityDate->isoWeekday(), 'start_time' => '18:00', 'end_time' => '20:00']);
        $provider = User::factory()->create(['role' => 'provider']);
        $place = BusinessPlace::factory()->create(['provider_id' => $provider->id, 'latitude' => -8.66, 'longitude' => 115.22]);
        $service = BusinessService::factory()->create(['business_place_id' => $place->id, 'business_category_id' => $sport->id, 'price_per_hour' => 120000]);
        ServiceSchedule::factory()->create(['business_service_id' => $service->id, 'day_of_week' => $activityDate->isoWeekday(), 'start_time' => '18:00', 'end_time' => '21:00']);

        $this->actingAs($creator)->post(route('sport-groups.store'), ['business_category_id' => $sport->id, 'level' => 'beginner', 'name' => 'Futsal Jumat', 'activity_date' => $activityDate->toDateString(), 'start_time' => '18:00', 'end_time' => '20:00', 'max_members' => 2])->assertRedirect();
        $group = SportGroup::query()->latest('id')->firstOrFail();

        $this->actingAs($matchingUser)->get(route('sport-groups.index'))->assertOk()->assertSee($group->name)->assertSee($place->name)->assertSee('Pilih dan ikut kelompok');
        $this->actingAs($differentLevel)->get(route('sport-groups.index'))->assertOk()->assertDontSee($group->name);
        $this->actingAs($matchingUser)->post(route('sport-groups.join', $group))->assertRedirect(route('sport-groups.show', $group));
        $this->assertDatabaseHas('sport_group_user', ['sport_group_id' => $group->id, 'user_id' => $matchingUser->id, 'status' => 'accepted']);
        $this->assertDatabaseHas('sport_groups', ['id' => $group->id, 'selected_business_service_id' => $service->id]);
    }

    public function test_group_cannot_be_created_without_an_available_place(): void
    {
        $creator = User::factory()->create(['role' => 'user']);
        $sport = BusinessCategory::query()->where('slug', 'futsal')->firstOrFail();
        $activityDate = today()->addDays(6);

        $this->actingAs($creator)->post(route('sport-groups.store'), ['business_category_id' => $sport->id, 'level' => 'beginner', 'name' => 'Tanpa Lapangan', 'activity_date' => $activityDate->toDateString(), 'start_time' => '18:00', 'end_time' => '20:00', 'max_members' => 2])->assertSessionHasErrors('activity_date');
        $this->assertDatabaseMissing('sport_groups', ['name' => 'Tanpa Lapangan']);
    }
}
