<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BusinessPlace;
use App\Models\BusinessService;
use App\Models\ProviderApplication;
use App\Models\ProviderPlan;
use App\Models\ProviderPlanUpgrade;
use App\Models\ServiceClosure;
use App\Models\ServiceSchedule;
use App\Models\User;
use App\Services\ProviderPlanLimitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View|RedirectResponse
    {
        if (auth()->user()->isProviderStaff()) {
            return redirect()->route('provider.bookings.index');
        }

        if (auth()->user()->isProvider()) {
            $provider = auth()->user();
            app(ProviderPlanLimitService::class)->deactivateExpiredBusinesses($provider);
            $provider->refresh();
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
            $user = auth()->user();
            $latestApplication = $user->providerApplications()->latest()->first();
            $upcomingBookingCount = $user->bookings()
                ->whereIn('status', ['held', 'payment_submitted', 'confirmed'])
                ->where(function ($query): void {
                    $query->whereDate('booking_date', '>', today())
                        ->orWhere(function ($todayQuery): void {
                            $todayQuery->whereDate('booking_date', today())
                                ->where('end_time', '>', now()->format('H:i:s'));
                        });
                })
                ->count();

            return view('dashboard.user', [
                'latestApplication' => $latestApplication,
                'upcomingBookingCount' => $upcomingBookingCount,
                'bookingCount' => $user->bookings()->count(),
                'profileCompletion' => collect([$user->name, $user->email, $user->phone, $user->location, $user->avatar])->filter()->count() * 20,
            ]);
        }

        if (auth()->user()->isAdmin()) {
            $monthStart = now()->startOfMonth();
            $monthEnd = now()->endOfMonth();
            $chartStart = now()->subMonths(5)->startOfMonth();
            $monthLabels = [];
            $bookingTrend = [];
            $upgradeTrend = [];

            for ($month = $chartStart->copy(); $month <= $monthEnd; $month->addMonth()) {
                $monthLabels[] = $month->translatedFormat('M Y');
                $bookingTrend[] = Booking::query()->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])->count();
                $upgradeTrend[] = ProviderPlanUpgrade::query()->where('status', 'approved')->whereBetween('reviewed_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])->count();
            }

            $bookingStatuses = Booking::query()
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');
            $recentUpgrades = ProviderPlanUpgrade::query()
                ->with(['provider', 'providerPlan'])
                ->latest()
                ->limit(5)
                ->get();
            $providerTotal = User::query()->where('role', 'provider')->count();
            $planDistribution = ProviderPlan::query()
                ->withCount(['providers' => fn ($query) => $query->where('role', 'provider')])
                ->orderByDesc('providers_count')
                ->orderBy('name')
                ->get()
                ->map(fn (ProviderPlan $plan): array => [
                    'name' => $plan->name,
                    'count' => $plan->providers_count,
                    'percentage' => $providerTotal > 0 ? round(($plan->providers_count / $providerTotal) * 100) : 0,
                    'is_active' => $plan->is_active,
                ]);
            $unassignedProviders = User::query()->where('role', 'provider')->whereNull('provider_plan_id')->count();

            return view('dashboard.admin', [
                'stats' => [
                    ['label' => 'Total pengguna', 'value' => User::query()->count(), 'icon' => 'fas fa-users', 'color' => 'primary', 'note' => 'Semua akun terdaftar'],
                    ['label' => 'Provider aktif', 'value' => User::query()->where('role', 'provider')->count(), 'icon' => 'fas fa-store', 'color' => 'success', 'note' => 'Pemilik bisnis terdaftar'],
                    ['label' => 'Tempat bisnis', 'value' => BusinessPlace::query()->count(), 'icon' => 'fas fa-building', 'color' => 'warning', 'note' => 'Lokasi di aplikasi'],
                    ['label' => 'Booking bulan ini', 'value' => Booking::query()->whereBetween('created_at', [$monthStart, $monthEnd])->count(), 'icon' => 'fas fa-calendar-check', 'color' => 'danger', 'note' => 'Aktivitas bulan berjalan'],
                ],
                'monthLabels' => $monthLabels,
                'bookingTrend' => $bookingTrend,
                'upgradeTrend' => $upgradeTrend,
                'bookingStatuses' => $bookingStatuses,
                'recentUpgrades' => $recentUpgrades,
                'planDistribution' => $planDistribution,
                'providerTotal' => $providerTotal,
                'unassignedProviders' => $unassignedProviders,
                'pendingApplications' => ProviderApplication::query()->where('status', 'pending')->count(),
                'pendingUpgrades' => ProviderPlanUpgrade::query()->where('status', 'pending')->count(),
                'confirmedRevenue' => Booking::query()->where('status', 'confirmed')->whereBetween('created_at', [$monthStart, $monthEnd])->sum('total_cost'),
                'approvedUpgradeRevenue' => ProviderPlanUpgrade::query()->where('status', 'approved')->whereBetween('reviewed_at', [$monthStart, $monthEnd])->sum('amount'),
            ]);
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
