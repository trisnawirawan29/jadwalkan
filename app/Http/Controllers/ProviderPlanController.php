<?php

namespace App\Http\Controllers;

use App\Models\ProviderPlan;
use App\Models\Setting;
use App\Services\ProviderPlanLimitService;
use App\Services\ProviderPlanProrationService;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProviderPlanController extends Controller
{
    public function index(Request $request, ProviderPlanLimitService $planLimits, ProviderPlanProrationService $proration): View
    {
        $planLimits->deactivateExpiredBusinesses($request->user());
        $plans = ProviderPlan::query()->where('is_active', true)->orderBy('monthly_price')->get();
        $pendingUpgrade = $request->user()->planUpgradeRequests()->where('status', 'pending')->with('providerPlan')->latest()->first();
        $upgradeRequests = $request->user()->planUpgradeRequests()->with('providerPlan')->latest()->get();

        $estimatedStartsAt = now()->startOfDay();
        $estimatedExpiresAt = $estimatedStartsAt->copy()->addMonth()->subDay()->endOfDay();
        $settings = Setting::query()->pluck('value', 'key');
        $paymentSettings = [
            'bank_name' => $settings['provider_plan_bank_name'] ?? null,
            'bank_account_name' => $settings['provider_plan_bank_account_name'] ?? null,
            'bank_account_number' => $settings['provider_plan_bank_account_number'] ?? null,
            'qris_url' => filled($settings['provider_plan_qris_image'] ?? null)
                ? Storage::disk('public')->url($settings['provider_plan_qris_image'])
                : null,
        ];
        $upgradeQuotes = $plans->mapWithKeys(fn (ProviderPlan $plan): array => [$plan->id => $proration->quote($request->user(), $plan)])->all();

        return view('provider.plans.index', compact('plans', 'pendingUpgrade', 'upgradeRequests', 'estimatedStartsAt', 'estimatedExpiresAt', 'paymentSettings', 'upgradeQuotes'));
    }

    public function store(Request $request, ProviderPlan $providerPlan, ProviderPlanProrationService $proration): RedirectResponse
    {
        abort_unless($providerPlan->is_active, 404);

        if ($request->user()->provider_plan_id === $providerPlan->id && ($providerPlan->isFree() || $request->user()->provider_plan_expires_at?->isFuture())) {
            return back()->withErrors(['provider_plan' => 'Paket ini sudah aktif pada akun Anda.']);
        }

        if ($request->user()->planUpgradeRequests()->where('status', 'pending')->exists()) {
            return back()->withErrors(['provider_plan' => 'Anda masih memiliki pengajuan upgrade yang sedang menunggu validasi.']);
        }

        $data = $request->validate([
            'payment_method' => ['required', 'in:bank_transfer,qris'],
            'payment_proof' => [$providerPlan->monthly_price > 0 ? 'required' : 'nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);
        $quote = $proration->quote($request->user(), $providerPlan);
        $proofPath = $request->file('payment_proof')?->store('provider-plan-proofs', 'public');
        $upgrade = $request->user()->planUpgradeRequests()->create([
            'provider_plan_id' => $providerPlan->id,
            'amount' => $quote['amount'],
            'is_prorated' => $quote['is_prorated'],
            'proration_remaining_days' => $quote['is_prorated'] ? $quote['remaining_days'] : null,
            'proration_total_days' => $quote['is_prorated'] ? $quote['total_days'] : null,
            'payment_method' => $data['payment_method'],
            'payment_proof' => $proofPath,
            'status' => 'pending',
        ]);
        AuditLogger::record('provider_plan_upgrade.created', "Pengajuan upgrade paket {$providerPlan->name} dibuat.", $upgrade, [], $upgrade->only(['provider_plan_id', 'amount', 'status']), $request);

        return redirect()->route('provider.plans.index')->with('success', 'Pengajuan upgrade berhasil dikirim dan sedang menunggu validasi superadmin.');
    }
}
