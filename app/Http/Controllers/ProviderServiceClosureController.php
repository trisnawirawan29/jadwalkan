<?php

namespace App\Http\Controllers;

use App\Models\BusinessPlace;
use App\Models\BusinessService;
use App\Models\ServiceClosure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProviderServiceClosureController extends Controller
{
    public function store(Request $request, BusinessPlace $businessPlace, BusinessService $businessService): RedirectResponse
    {
        $this->authorize('view', $businessPlace);
        $this->authorize('update', $businessService);

        $validator = Validator::make($request->all(), [
            'closure_date' => ['required', 'date', 'after_or_equal:today'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        $validator->after(function ($validator) use ($request, $businessService): void {
            if ($request->filled('closure_date') && ServiceClosure::query()
                ->where('business_service_id', $businessService->id)
                ->whereDate('closure_date', $request->input('closure_date'))
                ->exists()) {
                $validator->errors()->add('closure_date', 'Tanggal penutupan sudah terdaftar untuk layanan ini.');
            }
        });
        $data = $validator->validate();
        $businessService->closures()->create($data);

        return back()->with('success', 'Tanggal penutupan berhasil ditambahkan.');
    }

    public function destroy(BusinessPlace $businessPlace, BusinessService $businessService, ServiceClosure $serviceClosure): RedirectResponse
    {
        $this->authorize('view', $businessPlace);
        $this->authorize('update', $businessService);
        abort_unless($serviceClosure->business_service_id === $businessService->id, 404);
        $serviceClosure->delete();

        return back()->with('success', 'Tanggal penutupan berhasil dihapus.');
    }
}
