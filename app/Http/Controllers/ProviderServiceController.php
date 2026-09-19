<?php

namespace App\Http\Controllers;

use App\Models\BusinessCategory;
use App\Models\BusinessPlace;
use App\Models\BusinessService;
use App\Services\ProviderPlanLimitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProviderServiceController extends Controller
{
    public function index(BusinessPlace $businessPlace): View
    {
        $this->authorize('view', $businessPlace);
        $businessPlace->load(['services' => function ($query): void {
            $query->withCount('schedules')
                ->with([
                    'schedules' => fn ($scheduleQuery) => $scheduleQuery->orderBy('day_of_week')->orderBy('start_time'),
                    'closures' => fn ($closureQuery) => $closureQuery->where('closure_date', '>=', today())->where('is_active', true)->orderBy('closure_date'),
                    'businessCategory',
                ])
                ->latest();
        }]);

        return view('provider.services.index', compact('businessPlace'));
    }

    public function create(BusinessPlace $businessPlace): View
    {
        $this->authorize('view', $businessPlace);
        $this->authorize('create', BusinessService::class);

        return view('provider.services.form', ['businessPlace' => $businessPlace, 'businessService' => new BusinessService, 'categories' => $this->categories()]);
    }

    public function store(Request $request, BusinessPlace $businessPlace, ProviderPlanLimitService $planLimits): RedirectResponse
    {
        $this->authorize('view', $businessPlace);
        $this->authorize('create', BusinessService::class);
        $planLimits->ensureCanCreateService($businessPlace);
        $data = $request->validate($this->rules());
        $hourlyPrices = $this->normalizeHourlyPrices($data['hourly_prices'] ?? []);
        unset($data['hourly_prices']);
        $data['cover_image'] = $request->file('cover_image')?->store('business-services', 'public');
        $businessService = $businessPlace->services()->create($data);
        $this->syncHourlyPrices($businessService, $hourlyPrices);

        return redirect()->route('provider.business-places.services.index', $businessPlace)->with('success', 'Layanan berhasil ditambahkan.');
    }

    public function show(BusinessPlace $businessPlace, BusinessService $businessService): View
    {
        $this->authorize('view', $businessPlace);
        $this->authorize('view', $businessService);

        return view('provider.services.show', compact('businessPlace', 'businessService'));
    }

    public function edit(BusinessPlace $businessPlace, BusinessService $businessService): View
    {
        $this->authorize('view', $businessPlace);
        $this->authorize('update', $businessService);
        $businessService->load(['schedules', 'hourlyPrices']);

        return view('provider.services.form', compact('businessPlace', 'businessService') + ['categories' => $this->categories()]);
    }

    public function update(Request $request, BusinessPlace $businessPlace, BusinessService $businessService): RedirectResponse
    {
        $this->authorize('view', $businessPlace);
        $this->authorize('update', $businessService);
        $data = $request->validate($this->rules());
        $hourlyPrices = $this->normalizeHourlyPrices($data['hourly_prices'] ?? []);
        unset($data['hourly_prices']);
        if ($request->hasFile('cover_image')) {
            if ($businessService->cover_image) {
                Storage::disk('public')->delete($businessService->cover_image);
            }
            $data['cover_image'] = $request->file('cover_image')->store('business-services', 'public');
        }
        $businessService->update($data);
        $this->syncHourlyPrices($businessService, $hourlyPrices);

        return redirect()->route('provider.business-places.services.index', $businessPlace)->with('success', 'Layanan berhasil diperbarui.');
    }

    public function destroy(BusinessPlace $businessPlace, BusinessService $businessService): RedirectResponse
    {
        $this->authorize('view', $businessPlace);
        $this->authorize('delete', $businessService);
        if ($businessService->cover_image) {
            Storage::disk('public')->delete($businessService->cover_image);
        }
        $businessService->delete();

        return back()->with('success', 'Layanan berhasil dihapus.');
    }

    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'business_category_id' => ['nullable', 'integer', Rule::exists('business_categories', 'id')->where(fn ($query) => $query->where('is_active', true))],
            'type' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price_per_hour' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'hourly_prices' => ['nullable', 'array'],
            'hourly_prices.*' => ['nullable', 'array'],
            'hourly_prices.*.*' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    private function categories()
    {
        return BusinessCategory::query()
            ->where('is_active', true)
            ->with(['children' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')->orderBy('name')])
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $hourlyPrices
     * @return array<string, array<string, float>>
     */
    private function normalizeHourlyPrices(array $hourlyPrices): array
    {
        $normalized = [];

        foreach ($hourlyPrices as $day => $prices) {
            if (is_array($prices) && preg_match('/^[1-7]$/', (string) $day) === 1) {
                foreach ($prices as $time => $price) {
                    if (preg_match('/^(?:[01]\d|2[0-3]):00$/', (string) $time) === 1 && $price !== null && $price !== '') {
                        $normalized[(string) $day][(string) $time] = round((float) $price, 2);
                    }
                }
            }
        }

        return $normalized;
    }

    /**
     * @param  array<string, array<string, float>>  $hourlyPrices
     */
    private function syncHourlyPrices(BusinessService $businessService, array $hourlyPrices): void
    {
        $businessService->hourlyPrices()->delete();

        $rows = [];
        foreach ($hourlyPrices as $day => $prices) {
            foreach ($prices as $startTime => $price) {
                $rows[] = ['day_of_week' => (int) $day, 'start_time' => $startTime, 'price' => $price];
            }
        }

        if ($rows !== []) {
            $businessService->hourlyPrices()->createMany($rows);
        }
    }
}
