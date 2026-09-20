<?php

namespace App\Http\Controllers;

use App\Models\ProviderPlan;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminProviderPlanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $plans = ProviderPlan::query()->withCount('providers')->orderBy('monthly_price')->get();

        return view('admin.provider-plans.index', compact('plans'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admin.provider-plans.form', ['providerPlan' => new ProviderPlan, 'pageTitle' => 'Tambah Paket Provider']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());
        $data['slug'] = Str::slug($data['name']).'-'.Str::lower(Str::random(6));
        $plan = ProviderPlan::create($data);
        AuditLogger::record('provider_plan.created', "Paket provider {$plan->name} dibuat.", $plan, [], $plan->toArray());

        return redirect()->route('admin.provider-plans.index')->with('success', 'Paket provider berhasil dibuat.');
    }

    /**
     * Display the specified resource.
     */
    public function edit(ProviderPlan $providerPlan): View
    {
        return view('admin.provider-plans.form', ['providerPlan' => $providerPlan, 'pageTitle' => 'Edit Paket Provider']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProviderPlan $providerPlan): RedirectResponse
    {
        $data = $request->validate($this->rules($providerPlan));
        if ($providerPlan->isFree()) {
            $data['is_active'] = true;
            $data['monthly_price'] = 0;
            $data['max_business_places'] = 1;
            $data['max_services_per_place'] = 1;
            $data['max_bookings_per_month'] = 10;
        }
        $data['slug'] = Str::slug($data['name']).'-'.Str::lower(Str::random(6));
        $providerPlan->update($data);
        AuditLogger::record('provider_plan.updated', "Paket provider {$providerPlan->name} diperbarui.", $providerPlan, $providerPlan->getOriginal(), $providerPlan->toArray());

        return redirect()->route('admin.provider-plans.index')->with('success', 'Paket provider berhasil diperbarui.');
    }

    public function toggleStatus(ProviderPlan $providerPlan): RedirectResponse
    {
        if ($providerPlan->isFree()) {
            return back()->withErrors(['provider_plan' => 'Paket Free adalah paket default dan tidak dapat dinonaktifkan.']);
        }

        $providerPlan->update(['is_active' => ! $providerPlan->is_active]);
        AuditLogger::record('provider_plan.status_changed', "Status paket provider {$providerPlan->name} diubah.", $providerPlan, ['is_active' => ! $providerPlan->is_active], ['is_active' => $providerPlan->is_active]);

        return back()->with('success', $providerPlan->is_active ? 'Paket provider diaktifkan.' : 'Paket provider dinonaktifkan. Provider baru tidak dapat memilih paket ini.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProviderPlan $providerPlan): RedirectResponse
    {
        if ($providerPlan->isFree()) {
            return back()->withErrors(['provider_plan' => 'Paket Free adalah paket default dan tidak dapat dihapus.']);
        }

        if ($providerPlan->providers()->exists()) {
            return back()->withErrors(['provider_plan' => 'Paket yang masih digunakan provider tidak dapat dihapus. Nonaktifkan paket tersebut terlebih dahulu.']);
        }

        $providerPlan->delete();

        return back()->with('success', 'Paket provider berhasil dihapus.');
    }

    private function rules(?ProviderPlan $providerPlan = null): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'max_business_places' => ['required', 'integer', 'min:1', 'max:100000'],
            'max_services_per_place' => ['required', 'integer', 'min:1', 'max:100000'],
            'monthly_price' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'max_bookings_per_month' => ['required', 'integer', 'min:1', 'max:100000000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
