<?php

namespace App\Http\Controllers;

use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProviderApplicationController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if ($request->user()->isProvider()) {
            return redirect()->route('dashboard');
        }

        return view('provider-applications.create', [
            'application' => $request->user()->providerApplications()->latest()->first(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->isProvider()) {
            return redirect()->route('dashboard');
        }

        if ($request->user()->providerApplications()->where('status', 'pending')->exists()) {
            return back()->withErrors(['application' => 'Pengajuan Anda sedang menunggu validasi superadmin.']);
        }

        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:150'],
            'business_description' => ['nullable', 'string', 'max:2000'],
            'business_address' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $application = $request->user()->providerApplications()->create($data + ['status' => 'pending']);
        AuditLogger::record('provider_application.created', "Pengajuan provider {$request->user()->email} dibuat.", $application, [], $application->only(['business_name', 'status']), $request);

        return redirect()->route('provider-application.create')->with('success', 'Pengajuan provider berhasil dikirim dan sedang menunggu validasi.');
    }
}
