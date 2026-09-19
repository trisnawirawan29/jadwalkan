<?php

namespace App\Http\Controllers;

use App\Models\ProviderPlanUpgrade;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminUpgradeFinancialReportController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'status' => ['nullable', 'in:all,pending,approved,rejected'],
            'payment_method' => ['nullable', 'in:all,bank_transfer,qris'],
            'upgrade_type' => ['nullable', 'in:all,prorated,full'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $month = Carbon::createFromFormat('Y-m', $filters['month'] ?? now()->format('Y-m'))->startOfMonth();
        $baseQuery = ProviderPlanUpgrade::query()
            ->with(['provider', 'providerPlan'])
            ->whereBetween('created_at', [$month, $month->copy()->endOfMonth()])
            ->when(($filters['status'] ?? 'approved') !== 'all', fn (Builder $query) => $query->where('status', $filters['status'] ?? 'approved'))
            ->when(($filters['payment_method'] ?? 'all') !== 'all', fn (Builder $query) => $query->where('payment_method', $filters['payment_method']))
            ->when(($filters['upgrade_type'] ?? 'all') !== 'all', fn (Builder $query) => $query->where('is_prorated', ($filters['upgrade_type'] ?? '') === 'prorated'))
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery->whereHas('provider', fn (Builder $providerQuery) => $providerQuery->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                        ->orWhereHas('providerPlan', fn (Builder $planQuery) => $planQuery->where('name', 'like', "%{$search}%"));
                });
            });
        $summary = (clone $baseQuery)->selectRaw('COUNT(*) as transaction_count, COALESCE(SUM(amount), 0) as total_revenue, COALESCE(AVG(amount), 0) as average_value')->first();
        $dailySummary = (clone $baseQuery)->selectRaw('DATE(created_at) as transaction_date, COUNT(*) as transaction_count, COALESCE(SUM(amount), 0) as total_revenue')->groupByRaw('DATE(created_at)')->orderBy('transaction_date')->get();
        $transactions = (clone $baseQuery)->latest()->paginate(15)->withQueryString();
        $maxDailyRevenue = max(1, (float) $dailySummary->max('total_revenue'));

        return view('admin.reports.upgrade-financial', compact('filters', 'month', 'summary', 'dailySummary', 'transactions', 'maxDailyRevenue'));
    }
}
