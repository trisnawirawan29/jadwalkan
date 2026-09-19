<?php

namespace App\Http\Controllers;

use App\Models\BusinessCategory;
use App\Models\BusinessPlace;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlaceDirectoryController extends Controller
{
    public function __invoke(Request $request): View
    {
        $activePlacesQuery = BusinessPlace::query()->where('is_active', true);
        $hasExplicitRegionFilter = $request->filled('province') || $request->filled('regency') || $request->filled('district');
        $useProfileLocation = $request->user() && ! $hasExplicitRegionFilter && ! $request->boolean('all_locations') && $request->user()->province_code;
        $regionFilters = [
            'province' => $useProfileLocation ? $request->user()->province_code : $request->input('province'),
            'regency' => $useProfileLocation ? $request->user()->regency_code : $request->input('regency'),
            'district' => $useProfileLocation ? $request->user()->district_code : $request->input('district'),
        ];
        $regionOptions = (clone $activePlacesQuery)
            ->select(['province_code', 'province_name', 'regency_code', 'regency_name', 'district_code', 'district_name'])
            ->get()
            ->unique(fn (BusinessPlace $place): string => implode('|', [$place->province_code, $place->regency_code, $place->district_code]))
            ->sortBy(fn (BusinessPlace $place): string => implode('|', [$place->province_name, $place->regency_name, $place->district_name]))
            ->values();

        $categories = BusinessCategory::query()
            ->where('is_active', true)
            ->withCount(['places' => static function (Builder $query): void {
                $query->where('business_places.is_active', true);
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->filter(static fn (BusinessCategory $category): bool => $category->places_count > 0)
            ->values();

        $places = (clone $activePlacesQuery)
            ->with('businessCategories:id,name,slug')
            ->withCount([
                'services as active_services_count' => static function (Builder $query): void {
                    $query->where('is_active', true);
                },
            ])
            ->when($regionFilters['province'], fn (Builder $query, string $province) => $query->where('province_code', $province))
            ->when($regionFilters['regency'], fn (Builder $query, string $regency) => $query->where('regency_code', $regency))
            ->when($regionFilters['district'], fn (Builder $query, string $district) => $query->where('district_code', $district))
            ->when($request->filled('category'), function (Builder $query) use ($request): void {
                $query->whereHas('businessCategories', fn (Builder $categoryQuery) => $categoryQuery->whereKey($request->integer('category')));
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('places.index', [
            'appName' => Setting::value('app_name', config('app.name')),
            'categories' => $categories,
            'places' => $places,
            'regionOptions' => $regionOptions,
            'regionFilters' => $regionFilters,
            'useProfileLocation' => $useProfileLocation,
        ]);
    }
}
