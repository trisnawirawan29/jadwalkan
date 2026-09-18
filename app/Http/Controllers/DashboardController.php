<?php

namespace App\Http\Controllers;

use App\Models\BusinessPlace;
use App\Models\BusinessService;
use App\Models\ServiceClosure;
use App\Models\ServiceSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View|RedirectResponse
    {
        if (auth()->user()->isProvider()) {
            $provider = auth()->user();
            $placeScope = fn ($query) => $query->where('provider_id', $provider->id);
            $businessPlaces = BusinessPlace::query()
                ->where('provider_id', $provider->id)
                ->with(['businessCategories.parent'])
                ->withCount('services')
                ->latest()
                ->get();
            $serviceCount = BusinessService::query()->whereHas('businessPlace', $placeScope)->count();
            $activeScheduleCount = ServiceSchedule::query()
                ->where('is_active', true)
                ->where('is_closed', false)
                ->whereHas('businessService.businessPlace', $placeScope)
                ->count();
            $upcomingClosures = ServiceClosure::query()
                ->where('is_active', true)
                ->where('closure_date', '>=', today())
                ->whereHas('businessService.businessPlace', $placeScope)
                ->with('businessService')
                ->orderBy('closure_date')
                ->limit(5)
                ->get();
            $mapPlaces = $businessPlaces->map(function (BusinessPlace $place): array {
                $address = implode(', ', array_filter([$place->address, $place->district_name, $place->regency_name, $place->province_name, 'Indonesia']));

                return [
                    'name' => $place->name,
                    'query' => $address !== 'Indonesia' ? $address : $place->google_maps_url,
                    'maps_url' => $place->google_maps_url,
                    'latitude' => $place->latitude,
                    'longitude' => $place->longitude,
                ];
            })->values();

            return view('dashboard.provider', [
                'businessPlaces' => $businessPlaces,
                'stats' => [
                    ['label' => 'Tempat bisnis', 'value' => $businessPlaces->count(), 'icon' => 'fas fa-building', 'color' => 'primary'],
                    ['label' => 'Layanan / lapangan', 'value' => $serviceCount, 'icon' => 'fas fa-layer-group', 'color' => 'success'],
                    ['label' => 'Jadwal aktif', 'value' => $activeScheduleCount, 'icon' => 'fas fa-calendar-check', 'color' => 'warning'],
                    ['label' => 'Penutupan mendatang', 'value' => $upcomingClosures->count(), 'icon' => 'fas fa-calendar-xmark', 'color' => 'danger'],
                ],
                'upcomingClosures' => $upcomingClosures,
                'mapPlaces' => $mapPlaces,
            ]);
        }

        if (auth()->user()->hasRole('manager')) {
            return view('dashboard.manager');
        }

        if (auth()->user()->hasRole('user')) {
            return view('dashboard.user');
        }

        return view('dashboard', [
            'stats' => [
                ['label' => 'Total Pengguna', 'value' => '1,248', 'change' => '+12.5%', 'icon' => 'fas fa-users', 'color' => 'primary'],
                ['label' => 'Pendapatan Bulan Ini', 'value' => 'Rp 48,6 jt', 'change' => '+8.2%', 'icon' => 'fas fa-wallet', 'color' => 'success'],
                ['label' => 'Pesanan Baru', 'value' => '356', 'change' => '+5.7%', 'icon' => 'fas fa-shopping-bag', 'color' => 'warning'],
                ['label' => 'Tiket Terbuka', 'value' => '24', 'change' => '-3.1%', 'icon' => 'fas fa-headset', 'color' => 'danger'],
            ],
        ]);
    }
}
