<?php

namespace App\Http\Controllers;

use App\Models\BusinessCategory;
use App\Models\BusinessPlace;
use App\Models\BusinessService;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    public function __invoke(): View
    {
        $activePlaceFilter = static function (Builder $query): void {
            $query->where('business_places.is_active', true);
        };

        $activePlaces = BusinessPlace::query()
            ->where('is_active', true)
            ->with('businessCategories:id,name')
            ->withCount([
                'services as active_services_count' => static function (Builder $query): void {
                    $query->where('is_active', true);
                },
            ])
            ->latest()
            ->get();

        $categories = BusinessCategory::query()
            ->where('is_active', true)
            ->withCount(['places' => $activePlaceFilter])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->filter(static fn (BusinessCategory $category): bool => $category->places_count > 0)
            ->values();

        $activeServiceCount = BusinessService::query()
            ->where('is_active', true)
            ->whereHas('businessPlace', static function (Builder $query): void {
                $query->where('is_active', true);
            })
            ->count();

        $mapPlaces = $activePlaces
            ->filter(static fn (BusinessPlace $place): bool => $place->latitude !== null && $place->longitude !== null)
            ->map(static function (BusinessPlace $place): array {
                return [
                    'name' => $place->name,
                    'address' => $place->address,
                    'latitude' => $place->latitude,
                    'longitude' => $place->longitude,
                    'mapsUrl' => $place->google_maps_url,
                ];
            })
            ->values();

        return view('welcome', [
            'appName' => Setting::value('app_name', config('app.name')),
            'activePlaces' => $activePlaces,
            'activeServiceCount' => $activeServiceCount,
            'categories' => $categories,
            'mapPlaces' => $mapPlaces,
        ]);
    }
}
