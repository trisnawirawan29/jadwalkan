<?php

namespace App\Http\Controllers;

use App\Models\ProviderApplication;
use App\Models\ProviderPlan;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminProviderApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $applications = ProviderApplication::query()
            ->with(['user', 'reviewer'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->latest()
            ->get();

        return view('admin.provider-applications', compact('applications'));
    }

    public function approve(Request $request, ProviderApplication $providerApplication): RedirectResponse
    {
        if ($providerApplication->status !== 'pending') {
            return back()->withErrors(['application' => 'Pengajuan ini sudah divalidasi sebelumnya.']);
        }

        DB::transaction(function () use ($providerApplication, $request): void {
            $providerApplication->user()->update([
                'role' => 'provider',
                'provider_plan_id' => ProviderPlan::query()->where('is_free', true)->value('id'),
                'provider_plan_started_at' => now(),
                'provider_plan_expires_at' => null,
            ]);
            $providerApplication->update([
                'status' => 'approved',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ]);
        });
        AuditLogger::record('provider_application.approved', "Pengajuan provider {$providerApplication->user->email} disetujui.", $providerApplication, ['status' => 'pending'], ['status' => 'approved'], $request);

        return back()->with('success', 'Pengajuan disetujui. Pengguna sekarang memiliki akses provider.');
    }

    public function reject(Request $request, ProviderApplication $providerApplication): RedirectResponse
    {
        if ($providerApplication->status !== 'pending') {
            return back()->withErrors(['application' => 'Pengajuan ini sudah divalidasi sebelumnya.']);
        }

        $data = $request->validate(['rejection_reason' => ['required', 'string', 'max:1000']]);
        $providerApplication->update([
            'status' => 'rejected',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'rejection_reason' => $data['rejection_reason'],
        ]);
        AuditLogger::record('provider_application.rejected', "Pengajuan provider {$providerApplication->user->email} ditolak.", $providerApplication, ['status' => 'pending'], ['status' => 'rejected', 'rejection_reason' => $data['rejection_reason']], $request);

        return back()->with('success', 'Pengajuan provider ditolak.');
    }
}
