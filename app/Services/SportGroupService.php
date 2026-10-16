<?php

namespace App\Services;

use App\Models\BusinessService;
use App\Models\SportGroup;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

class SportGroupService
{
    private const DEFAULT_NEARBY_RADIUS_KM = 25.0;

    /**
     * Return open groups that match at least one of the user's sport slots.
     *
     * @return SupportCollection<int, SportGroup>
     */
    public function matchingGroupsFor(User $user): SupportCollection
    {
        $user->loadMissing('sportPreferences');
        $groups = SportGroup::query()
            ->with(['businessCategory', 'creator', 'selectedBusinessService.businessPlace'])
            ->withCount(['members as accepted_members_count' => fn ($query) => $query->where('sport_group_user.status', 'accepted')])
            ->where('status', 'open')
            ->whereNotNull('selected_business_service_id')
            ->whereDate('activity_date', '>=', today())
            ->whereDoesntHave('members', fn ($query) => $query->whereKey($user->id))
            ->orderBy('activity_date')
            ->orderBy('start_time')
            ->limit(100)
            ->get();

        return $groups
            ->filter(fn (SportGroup $group): bool => $this->userMatchesGroup($user, $group) && $this->isPlaceAvailable($group))
            ->values();
    }

    /**
     * Return the most common sport schedules among users near the given user.
     *
     * @return SupportCollection<int, array{sport_name: string, level: string, day_of_week: int, start_time: string, end_time: string, user_count: int, average_distance_km: float}>
     */
    public function popularSchedulesAround(User $user, ?int $businessCategoryId = null, float $radiusKm = self::DEFAULT_NEARBY_RADIUS_KM): SupportCollection
    {
        if ($user->latitude === null || $user->longitude === null) {
            return collect();
        }

        $nearbyUsers = User::query()
            ->select(['id', 'latitude', 'longitude'])
            ->where('id', '!=', $user->id)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with(['sportPreferences.businessCategory'])
            ->get()
            ->map(function (User $nearbyUser) use ($user, $radiusKm): ?User {
                $distance = $this->distanceInKilometers(
                    (float) $user->latitude,
                    (float) $user->longitude,
                    (float) $nearbyUser->latitude,
                    (float) $nearbyUser->longitude,
                );

                return $distance <= $radiusKm ? $nearbyUser->setAttribute('distance_from_user', $distance) : null;
            })
            ->filter();

        $scheduleSummaries = [];

        foreach ($nearbyUsers as $nearbyUser) {
            foreach ($nearbyUser->sportPreferences as $preference) {
                if ($businessCategoryId !== null && $preference->business_category_id !== $businessCategoryId) {
                    continue;
                }

                $key = implode('|', [
                    $preference->business_category_id,
                    $preference->level,
                    $preference->day_of_week,
                    $preference->start_time,
                    $preference->end_time,
                ]);

                $scheduleSummaries[$key] ??= [
                    'sport_name' => $preference->businessCategory->name,
                    'level' => $preference->level,
                    'day_of_week' => (int) $preference->day_of_week,
                    'start_time' => $preference->start_time,
                    'end_time' => $preference->end_time,
                    'user_ids' => [],
                    'distance_total' => 0.0,
                ];
                $scheduleSummaries[$key]['user_ids'][$nearbyUser->id] = true;
                $scheduleSummaries[$key]['distance_total'] += (float) $nearbyUser->distance_from_user;
            }
        }

        return collect($scheduleSummaries)
            ->map(function (array $summary): array {
                $userCount = count($summary['user_ids']);

                return [
                    'sport_name' => $summary['sport_name'],
                    'level' => $summary['level'],
                    'day_of_week' => $summary['day_of_week'],
                    'start_time' => $summary['start_time'],
                    'end_time' => $summary['end_time'],
                    'user_count' => $userCount,
                    'average_distance_km' => round($summary['distance_total'] / $userCount, 1),
                ];
            })
            ->sort(function (array $first, array $second): int {
                return [$second['user_count'], $first['average_distance_km'], $first['sport_name']]
                    <=> [$first['user_count'], $second['average_distance_km'], $second['sport_name']];
            })
            ->values()
            ->take(12);
    }

    public function userMatchesGroup(User $user, SportGroup $sportGroup): bool
    {
        $user->loadMissing('sportPreferences');
        $dayOfWeek = $sportGroup->activity_date->isoWeekday();

        return $user->sportPreferences->contains(function ($preference) use ($sportGroup, $dayOfWeek): bool {
            return $preference->business_category_id === $sportGroup->business_category_id
                && $preference->level === $sportGroup->level
                && $preference->day_of_week === $dayOfWeek
                && $preference->start_time <= $sportGroup->start_time
                && $preference->end_time >= $sportGroup->end_time;
        });
    }

    public function assignBestAvailablePlace(SportGroup $sportGroup): ?BusinessService
    {
        $service = $this->recommendations($sportGroup)->first();

        if ($service) {
            $sportGroup->update(['selected_business_service_id' => $service->id]);
        }

        return $service;
    }

    public function isPlaceAvailable(SportGroup $sportGroup): bool
    {
        if (! $sportGroup->selected_business_service_id) {
            return false;
        }

        return BusinessService::query()
            ->whereKey($sportGroup->selected_business_service_id)
            ->whereHas('schedules', function ($query) use ($sportGroup): void {
                $query->where('day_of_week', $sportGroup->activity_date->isoWeekday())
                    ->where('is_active', true)
                    ->where('start_time', '<=', $sportGroup->start_time)
                    ->where('end_time', '>=', $sportGroup->end_time)
                    ->whereDoesntHave('bookings', function ($bookingQuery) use ($sportGroup): void {
                        $bookingQuery->whereDate('booking_date', $sportGroup->activity_date)
                            ->whereIn('status', ['held', 'payment_submitted', 'confirmed'])
                            ->where('start_time', '<', $sportGroup->end_time)
                            ->where('end_time', '>', $sportGroup->start_time);
                    });
            })
            ->exists();
    }

    public function joinGroup(User $user, SportGroup $sportGroup): void
    {
        DB::transaction(function () use ($user, $sportGroup): void {
            $lockedGroup = SportGroup::query()->whereKey($sportGroup->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedGroup->status === 'open', 422, 'Kelompok ini sudah ditutup.');
            abort_unless($this->userMatchesGroup($user, $lockedGroup), 422, 'Kelompok ini tidak cocok dengan jadwal, olahraga, atau level Anda.');
            abort_unless($this->isPlaceAvailable($lockedGroup), 422, 'Lapangan kelompok ini sudah tidak tersedia pada jadwal tersebut.');

            $alreadyMember = $lockedGroup->members()->whereKey($user->id)->exists();
            abort_unless(! $alreadyMember, 422, 'Anda sudah terdaftar di kelompok ini.');
            abort_unless($lockedGroup->members()->wherePivot('status', 'accepted')->count() < $lockedGroup->max_members, 422, 'Kuota kelompok ini sudah penuh.');

            $lockedGroup->members()->attach($user->id, ['status' => 'accepted', 'responded_at' => now()]);
        });
    }

    /**
     * Invite the closest matching users until the group has enough candidates.
     */
    public function inviteMatchingUsers(SportGroup $sportGroup, int $minimumInvites = 0): void
    {
        $currentCandidates = $sportGroup->members()->wherePivotIn('status', ['invited', 'accepted'])->count();
        $needed = max($minimumInvites, $sportGroup->max_members) - $currentCandidates;

        if ($needed <= 0) {
            return;
        }

        $existingUserIds = $sportGroup->members()->pluck('users.id');
        $candidates = User::query()
            ->where('users.id', '!=', $sportGroup->creator_id)
            ->whereNotIn('id', $existingUserIds)
            ->whereHas('sportPreferences', function ($query) use ($sportGroup): void {
                $query->where('business_category_id', $sportGroup->business_category_id)
                    ->where('level', $sportGroup->level)
                    ->where('day_of_week', $sportGroup->activity_date->isoWeekday())
                    ->where('start_time', '<=', $sportGroup->start_time)
                    ->where('end_time', '>=', $sportGroup->end_time);
            })
            ->orderByRaw('latitude IS NULL, longitude IS NULL')
            ->orderBy('id')
            ->limit($needed)
            ->get();

        foreach ($candidates as $candidate) {
            $sportGroup->members()->attach($candidate->id, ['status' => 'invited']);
            $candidate->notify(new SystemNotification(
                "Undangan kelompok {$sportGroup->name}",
                "Anda diundang mengikuti kegiatan {$sportGroup->businessCategory->name} pada {$sportGroup->activity_date->format('d M Y')}.",
                'info',
                'community',
            ));
        }
    }

    /**
     * @return Collection<int, BusinessService>
     */
    public function recommendations(SportGroup $sportGroup): Collection
    {
        $sportGroup->loadMissing(['members', 'businessCategory']);
        $dayOfWeek = $sportGroup->activity_date->isoWeekday();
        $members = $sportGroup->members->whereIn('pivot.status', ['accepted', 'invited']);

        return BusinessService::query()
            ->with(['businessPlace', 'schedules' => fn ($query) => $query->where('day_of_week', $dayOfWeek)->where('is_active', true)])
            ->where('business_category_id', $sportGroup->business_category_id)
            ->where('is_active', true)
            ->whereHas('businessPlace', fn ($query) => $query->where('is_active', true))
            ->whereHas('schedules', function ($query) use ($dayOfWeek, $sportGroup): void {
                $query->where('day_of_week', $dayOfWeek)
                    ->where('is_active', true)
                    ->where('start_time', '<=', $sportGroup->start_time)
                    ->where('end_time', '>=', $sportGroup->end_time)
                    ->whereDoesntHave('bookings', function ($bookingQuery) use ($sportGroup): void {
                        $bookingQuery->whereDate('booking_date', $sportGroup->activity_date)
                            ->whereIn('status', ['held', 'payment_submitted', 'confirmed'])
                            ->where('start_time', '<', $sportGroup->end_time)
                            ->where('end_time', '>', $sportGroup->start_time);
                    });
            })
            ->get()
            ->sortBy(function (BusinessService $service) use ($members): array {
                $distance = $this->averageDistance($service, $members);

                return [
                    $distance ?? 999999,
                    (float) $service->price_per_hour,
                    $service->id,
                ];
            })
            ->values();
    }

    public function averageDistance(BusinessService $service, Collection $members): ?float
    {
        $place = $service->businessPlace;
        $distances = $members
            ->filter(fn (User $member): bool => $member->latitude !== null && $member->longitude !== null && $place->latitude !== null && $place->longitude !== null)
            ->map(fn (User $member): float => $this->distanceInKilometers((float) $member->latitude, (float) $member->longitude, (float) $place->latitude, (float) $place->longitude));

        return $distances->isEmpty() ? null : round($distances->average(), 1);
    }

    private function distanceInKilometers(float $latitudeA, float $longitudeA, float $latitudeB, float $longitudeB): float
    {
        $latitudeDelta = deg2rad($latitudeB - $latitudeA);
        $longitudeDelta = deg2rad($longitudeB - $longitudeA);
        $a = sin($latitudeDelta / 2) ** 2 + cos(deg2rad($latitudeA)) * cos(deg2rad($latitudeB)) * sin($longitudeDelta / 2) ** 2;

        return 6371 * 2 * asin(min(1, sqrt($a)));
    }
}
