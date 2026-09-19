<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BusinessPlace;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProviderFinancialReportController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'booking_date' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', 'in:all,held,payment_submitted,confirmed,rejected'],
            'business_place_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $month = Carbon::createFromFormat('Y-m', $filters['month'] ?? now()->format('Y-m'))->startOfMonth();
        $providerId = $request->user()->id;
        $places = BusinessPlace::query()
            ->where('provider_id', $providerId)
            ->orderBy('name')
            ->get(['id', 'name']);
        $baseQuery = Booking::query()
            ->with(['user', 'businessService.businessPlace'])
            ->whereBetween('booking_date', [$month->toDateString(), $month->copy()->endOfMonth()->toDateString()])
            ->whereHas('businessService.businessPlace', function (Builder $query) use ($providerId): void {
                $query->where('provider_id', $providerId);
            })
            ->when($filters['booking_date'] ?? null, fn (Builder $query, string $date) => $query->whereDate('booking_date', $date))
            ->when(($filters['status'] ?? 'confirmed') !== 'all', fn (Builder $query) => $query->where('status', $filters['status'] ?? 'confirmed'))
            ->when($filters['business_place_id'] ?? null, function (Builder $query, int $placeId): void {
                $query->whereHas('businessService', fn (Builder $serviceQuery) => $serviceQuery->where('business_place_id', $placeId));
            })
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery->where('booking_code', 'like', "%{$search}%")
                        ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('businessService', function (Builder $serviceQuery) use ($search): void {
                            $serviceQuery->where('name', 'like', "%{$search}%")
                                ->orWhereHas('businessPlace', fn (Builder $placeQuery) => $placeQuery->where('name', 'like', "%{$search}%"));
                        });
                });
            });
        $summary = (clone $baseQuery)
            ->selectRaw('COUNT(*) as transaction_count, COALESCE(SUM(total_cost), 0) as total_revenue, COALESCE(AVG(total_cost), 0) as average_value')
            ->first();
        $dailySummary = (clone $baseQuery)
            ->selectRaw('booking_date, COUNT(*) as transaction_count, COALESCE(SUM(total_cost), 0) as total_revenue')
            ->groupBy('booking_date')
            ->orderBy('booking_date')
            ->get();
        $transactions = (clone $baseQuery)
            ->orderByDesc('booking_date')
            ->orderByDesc('start_time')
            ->paginate(15)
            ->withQueryString();
        $maxDailyRevenue = max(1, (float) $dailySummary->max('total_revenue'));

        return view('provider.reports.financial', compact('filters', 'month', 'places', 'summary', 'dailySummary', 'transactions', 'maxDailyRevenue'));
    }
}
