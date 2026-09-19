<?php

namespace App\Http\Controllers;

use App\Models\ProviderPlanUpgrade;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminProviderPlanUpgradeController extends Controller
{
    public function index(Request $request): View
    {
        $upgrades = ProviderPlanUpgrade::query()
            ->with(['provider', 'providerPlan', 'reviewer'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->latest()
            ->get();

        return view('admin.provider-plan-upgrades', compact('upgrades'));
    }

    public function approve(Request $request, ProviderPlanUpgrade $providerPlanUpgrade): RedirectResponse
    {
        if ($providerPlanUpgrade->status !== 'pending') {
            return back()->withErrors(['upgrade' => 'Pengajuan upgrade ini sudah divalidasi sebelumnya.']);
        }

        DB::transaction(function () use ($providerPlanUpgrade, $request): void {
            $startedAt = now();
            $previousExpiresAt = $providerPlanUpgrade->provider->provider_plan_expires_at;
            $expiresAt = $previousExpiresAt?->isFuture() ? $previousExpiresAt : $startedAt->copy()->addMonth();
            $providerPlanUpgrade->provider()->update([
                'provider_plan_id' => $providerPlanUpgrade->provider_plan_id,
                'provider_plan_started_at' => $startedAt,
                'provider_plan_expires_at' => $expiresAt,
            ]);
            $providerPlanUpgrade->update([
                'status' => 'approved',
                'service_started_at' => $startedAt,
                'service_expires_at' => $expiresAt,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ]);
        });
        AuditLogger::record('provider_plan_upgrade.approved', "Upgrade paket {$providerPlanUpgrade->providerPlan->name} disetujui.", $providerPlanUpgrade, ['status' => 'pending'], ['status' => 'approved'], $request);

        return back()->with('success', 'Upgrade paket disetujui dan paket provider telah diaktifkan.');
    }

    public function reject(Request $request, ProviderPlanUpgrade $providerPlanUpgrade): RedirectResponse
    {
        if ($providerPlanUpgrade->status !== 'pending') {
            return back()->withErrors(['upgrade' => 'Pengajuan upgrade ini sudah divalidasi sebelumnya.']);
        }

        $data = $request->validate(['rejection_reason' => ['required', 'string', 'max:1000']]);
        $providerPlanUpgrade->update(['status' => 'rejected', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'rejection_reason' => $data['rejection_reason']]);
        AuditLogger::record('provider_plan_upgrade.rejected', "Upgrade paket {$providerPlanUpgrade->providerPlan->name} ditolak.", $providerPlanUpgrade, ['status' => 'pending'], ['status' => 'rejected', 'rejection_reason' => $data['rejection_reason']], $request);

        return back()->with('success', 'Pengajuan upgrade paket ditolak.');
    }
}
