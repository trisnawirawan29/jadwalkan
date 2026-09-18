<?php

namespace App\Http\Controllers;

use App\Models\BusinessCategory;
use App\Models\BusinessPlace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProviderBusinessPlaceController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', BusinessPlace::class);
        $businessPlaces = BusinessPlace::query()->whereBelongsTo($request->user(), 'provider')->with(['businessCategories.parent'])->withCount('services')->latest()->get();

        return view('provider.business-places.index', compact('businessPlaces'));
    }

    public function create(): View
    {
        $this->authorize('create', BusinessPlace::class);

        return view('provider.business-places.form', ['businessPlace' => new BusinessPlace, 'categories' => $this->categories()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', BusinessPlace::class);
        $data = $request->validate($this->rules());
        $data['cover_image'] = $request->file('cover_image')?->store('business-places', 'public');
        $categoryIds = $data['business_category_ids'] ?? (isset($data['business_category_id']) ? [$data['business_category_id']] : []);
        unset($data['business_category_ids'], $data['business_category_id']);
        $businessPlace = $request->user()->businessPlaces()->create($data);
        $businessPlace->businessCategories()->sync($categoryIds);

        return redirect()->route('provider.business-places.index')->with('success', 'Tempat bisnis berhasil ditambahkan.');
    }

    public function show(BusinessPlace $businessPlace): View
    {
        $this->authorize('view', $businessPlace);
        $businessPlace->load('businessCategories.parent');

        return view('provider.business-places.show', compact('businessPlace'));
    }

    public function edit(BusinessPlace $businessPlace): View
    {
        $this->authorize('update', $businessPlace);

        return view('provider.business-places.form', ['businessPlace' => $businessPlace, 'categories' => $this->categories()]);
    }

    public function update(Request $request, BusinessPlace $businessPlace): RedirectResponse
    {
        $this->authorize('update', $businessPlace);
        $data = $request->validate($this->rules());
        if ($request->hasFile('cover_image')) {
            $newCoverImage = $request->file('cover_image')->store('business-places', 'public');
            if ($businessPlace->cover_image) {
                Storage::disk('public')->delete($businessPlace->cover_image);
            }
            $data['cover_image'] = $newCoverImage;
        }
        $categoryIds = $data['business_category_ids'] ?? (isset($data['business_category_id']) ? [$data['business_category_id']] : []);
        unset($data['business_category_ids'], $data['business_category_id']);
        $businessPlace->update($data);
        $businessPlace->businessCategories()->sync($categoryIds);

        return redirect()->route('provider.business-places.index')->with('success', 'Tempat bisnis berhasil diperbarui.');
    }

    public function toggleStatus(BusinessPlace $businessPlace): RedirectResponse
    {
        $this->authorize('update', $businessPlace);
        $businessPlace->update(['is_active' => ! $businessPlace->is_active]);

        return back()->with('success', $businessPlace->is_active ? 'Tempat bisnis diaktifkan.' : 'Tempat bisnis dinonaktifkan.');
    }

    public function destroy(BusinessPlace $businessPlace): RedirectResponse
    {
        $this->authorize('delete', $businessPlace);
        if ($businessPlace->cover_image) {
            Storage::disk('public')->delete($businessPlace->cover_image);
        }
        $businessPlace->delete();

        return back()->with('success', 'Tempat bisnis berhasil dihapus.');
    }

    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'business_category_ids' => ['nullable', 'array'],
            'business_category_ids.*' => ['integer', Rule::exists('business_categories', 'id')->where(fn ($query) => $query->where('is_active', true))],
            'business_category_id' => ['nullable', 'integer', Rule::exists('business_categories', 'id')->where(fn ($query) => $query->where('is_active', true))],
            'cover_image' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:5120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'address' => ['nullable', 'string', 'max:255'],
            'google_maps_url' => ['nullable', 'url:https', 'max:2048', function (string $attribute, mixed $value, \Closure $fail): void {
                $host = strtolower((string) parse_url($value, PHP_URL_HOST));
                $isGoogleMapsHost = in_array($host, ['goo.gl', 'maps.google.com', 'maps.app.goo.gl'], true)
                    || str_contains($host, 'google.');

                if (! $isGoogleMapsHost) {
                    $fail('Link harus mengarah ke Google Maps.');
                }
            }],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'province_code' => ['nullable', 'string', 'max:10'],
            'province_name' => ['nullable', 'string', 'max:100'],
            'regency_code' => ['nullable', 'string', 'max:15'],
            'regency_name' => ['nullable', 'string', 'max:100'],
            'district_code' => ['nullable', 'string', 'max:20'],
            'district_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    private function categories()
    {
        return BusinessCategory::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->with(['children' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
