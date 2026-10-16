<?php

namespace Database\Seeders;

use App\Models\BusinessCategory;
use App\Models\BusinessPlace;
use App\Models\BusinessService;
use App\Models\ServiceSchedule;
use App\Models\SportGroup;
use App\Models\SportPreference;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class SportGroupDemoSeeder extends Seeder
{
    private const SPORT_SLUGS = ['bulutangkis', 'basket', 'futsal', 'tenis'];

    private const LEVELS = ['beginner', 'intermediate', 'advanced'];

    private const START_TIMES = ['07:00', '09:00', '16:00', '18:00', '20:00'];

    public function run(): void
    {
        fake()->seed(20261016);
        $sports = $this->sports();
        $provider = $this->provider();
        $services = $this->createPlacesAndServices($provider, $sports);
        $users = $this->createUsers();
        $this->createPreferences($users, $sports);
        $users = $users->map(fn (User $user): User => $user->load('sportPreferences'));
        $this->createGroups($users, $sports, $services);
    }

    /**
     * @return Collection<string, BusinessCategory>
     */
    private function sports(): Collection
    {
        $sports = BusinessCategory::query()->whereIn('slug', self::SPORT_SLUGS)->whereNotNull('parent_id')->get()->keyBy('slug');

        foreach (self::SPORT_SLUGS as $slug) {
            if (! $sports->has($slug)) {
                throw new RuntimeException("Sport {$slug} tidak ditemukan.");
            }
        }

        return $sports;
    }

    private function provider(): User
    {
        return User::query()->firstOrCreate(
            ['email' => 'demo.provider@example.com'],
            ['name' => 'Demo Sport Center', 'password' => Hash::make('password'), 'role' => 'provider'],
        );
    }

    /**
     * @param  Collection<string, BusinessCategory>  $sports
     * @return Collection<string, Collection<int, BusinessService>>
     */
    private function createPlacesAndServices(User $provider, Collection $sports): Collection
    {
        return $sports->mapWithKeys(function (BusinessCategory $sport, string $slug) use ($provider): array {
            $services = collect();

            foreach (range(1, 3) as $placeNumber) {
                $place = BusinessPlace::query()->firstOrCreate(
                    ['provider_id' => $provider->id, 'name' => ucfirst($sport->name).' Center '.$placeNumber],
                    ['address' => "Jl. Olahraga No. {$placeNumber}, Denpasar, Bali", 'latitude' => -8.65 + ($placeNumber * 0.006), 'longitude' => 115.21 + ($placeNumber * 0.006), 'is_active' => true],
                );
                $service = BusinessService::query()->firstOrCreate(
                    ['business_place_id' => $place->id, 'name' => $sport->name.' Court '.$placeNumber],
                    ['business_category_id' => $sport->id, 'type' => $sport->name, 'price_per_hour' => 90000 + ($placeNumber * 25000), 'is_active' => true],
                );
                $this->createWeeklySchedules($service);
                $services->push($service);
            }

            return [$slug => $services];
        });
    }

    private function createWeeklySchedules(BusinessService $service): void
    {
        foreach (range(1, 7) as $dayOfWeek) {
            ServiceSchedule::query()->firstOrCreate(
                ['business_service_id' => $service->id, 'day_of_week' => $dayOfWeek, 'start_time' => '06:00', 'end_time' => '23:00'],
                ['is_active' => true],
            );
        }
    }

    /**
     * @return Collection<int, User>
     */
    private function createUsers(): Collection
    {
        return collect(range(1, 100))->map(function (int $number): User {
            return User::query()->updateOrCreate(
                ['email' => sprintf('demo.user.%03d@example.com', $number)],
                ['name' => fake()->name(), 'password' => Hash::make('password'), 'role' => 'user', 'latitude' => -8.70 + fake()->randomFloat(4, 0, 0.12), 'longitude' => 115.18 + fake()->randomFloat(4, 0, 0.12)],
            );
        });
    }

    /**
     * @param  Collection<int, User>  $users
     * @param  Collection<string, BusinessCategory>  $sports
     */
    private function createPreferences(Collection $users, Collection $sports): void
    {
        $sportSlugs = $sports->keys()->values()->all();

        foreach ($users as $index => $user) {
            $preferenceCount = 1 + ($index % 3);
            foreach (range(0, $preferenceCount - 1) as $offset) {
                $sport = $sports->get($sportSlugs[($index + $offset) % count($sportSlugs)]);
                $dayOfWeek = (($index + ($offset * 2)) % 7) + 1;
                $startTime = self::START_TIMES[($index + $offset) % count(self::START_TIMES)];
                $endTime = Carbon::createFromFormat('H:i', $startTime)->addHours(2)->format('H:i');
                $this->preference($user, $sport, self::LEVELS[($index + $offset) % count(self::LEVELS)], $dayOfWeek, $startTime, $endTime);
            }
        }
    }

    /**
     * @param  Collection<int, User>  $users
     * @param  Collection<string, BusinessCategory>  $sports
     * @param  Collection<string, Collection<int, BusinessService>>  $services
     */
    private function createGroups(Collection $users, Collection $sports, Collection $services): void
    {
        $sportSlugs = $sports->keys()->values()->all();

        foreach (range(1, 24) as $number) {
            $sportSlug = $sportSlugs[($number - 1) % count($sportSlugs)];
            $sport = $sports->get($sportSlug);
            $level = self::LEVELS[($number - 1) % count(self::LEVELS)];
            $activityDate = today()->addDays(3 + (($number - 1) % 14));
            $startTime = self::START_TIMES[($number - 1) % count(self::START_TIMES)];
            $endTime = Carbon::createFromFormat('H:i', $startTime)->addHours(2)->format('H:i');
            $creator = $this->findOrCreateMatchingCreator($users, $sport, $level, $activityDate, $startTime, $endTime);
            $service = $services->get($sportSlug)->get(($number - 1) % 3);
            $group = SportGroup::query()->firstOrCreate(
                ['name' => sprintf('%s Demo %02d', $sport->name, $number), 'activity_date' => $activityDate->toDateString()],
                ['creator_id' => $creator->id, 'business_category_id' => $sport->id, 'level' => $level, 'selected_business_service_id' => $service->id, 'description' => 'Kelompok demo untuk simulasi pencocokan user.', 'start_time' => $startTime, 'end_time' => $endTime, 'max_members' => 6, 'status' => 'open'],
            );
            $group->members()->syncWithoutDetaching([$creator->id => ['status' => 'accepted', 'responded_at' => now()]]);
        }
    }

    private function findOrCreateMatchingCreator(Collection $users, BusinessCategory $sport, string $level, Carbon $date, string $startTime, string $endTime): User
    {
        $creator = $users->first(fn (User $user): bool => $user->sportPreferences->contains(fn ($preference): bool => $preference->business_category_id === $sport->id && $preference->level === $level && $preference->day_of_week === $date->isoWeekday() && $preference->start_time <= $startTime && $preference->end_time >= $endTime));

        if ($creator) {
            return $creator;
        }

        $creator = $users->firstOrFail();
        $this->preference($creator, $sport, $level, $date->isoWeekday(), $startTime, $endTime);

        return $creator;
    }

    private function preference(User $user, BusinessCategory $sport, string $level, int $dayOfWeek, string $startTime, string $endTime): void
    {
        SportPreference::query()->firstOrCreate(['user_id' => $user->id, 'business_category_id' => $sport->id, 'day_of_week' => $dayOfWeek, 'start_time' => $startTime, 'end_time' => $endTime], ['level' => $level]);
        $user->sportInterests()->syncWithoutDetaching([$sport->id]);
    }
}
