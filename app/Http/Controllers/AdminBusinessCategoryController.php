<?php

namespace App\Http\Controllers;

use App\Models\BusinessCategory;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminBusinessCategoryController extends Controller
{
    public function index(): View
    {
        $categories = BusinessCategory::query()
            ->whereNull('parent_id')
            ->with(['children' => fn ($query) => $query->withCount('places')])
            ->withCount('places')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.business-categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.business-categories.form', [
            'businessCategory' => new BusinessCategory,
            'parents' => $this->parents(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());
        $data['slug'] = Str::slug($data['name']);
        $category = BusinessCategory::create($data);
        AuditLogger::record('business_category.created', "Kategori bisnis {$category->name} dibuat.", $category, [], $category->only(['name', 'parent_id', 'is_active']));

        return redirect()->route('admin.business-categories.index')->with('success', 'Kategori bisnis berhasil ditambahkan.');
    }

    public function edit(BusinessCategory $businessCategory): View
    {
        return view('admin.business-categories.form', [
            'businessCategory' => $businessCategory,
            'parents' => $this->parents($businessCategory),
        ]);
    }

    public function update(Request $request, BusinessCategory $businessCategory): RedirectResponse
    {
        $data = $request->validate($this->rules($businessCategory));
        $data['slug'] = Str::slug($data['name']);
        $old = $businessCategory->only(['name', 'parent_id', 'is_active']);
        $businessCategory->update($data);
        AuditLogger::record('business_category.updated', "Kategori bisnis {$businessCategory->name} diperbarui.", $businessCategory, $old, $businessCategory->only(['name', 'parent_id', 'is_active']));

        return redirect()->route('admin.business-categories.index')->with('success', 'Kategori bisnis berhasil diperbarui.');
    }

    public function destroy(BusinessCategory $businessCategory): RedirectResponse
    {
        if ($businessCategory->children()->exists()) {
            return back()->withErrors(['category' => 'Kategori utama yang masih memiliki subkategori tidak dapat dihapus.']);
        }

        if ($businessCategory->places()->exists()) {
            return back()->withErrors(['category' => 'Kategori yang sudah digunakan tempat bisnis tidak dapat dihapus.']);
        }

        $name = $businessCategory->name;
        $businessCategory->delete();
        AuditLogger::record('business_category.deleted', "Kategori bisnis {$name} dihapus.");

        return back()->with('success', 'Kategori bisnis berhasil dihapus.');
    }

    private function parents(?BusinessCategory $except = null)
    {
        return BusinessCategory::query()
            ->whereNull('parent_id')
            ->when($except, fn ($query) => $query->whereKeyNot($except->id))
            ->orderBy('name')
            ->get();
    }

    private function rules(?BusinessCategory $category = null): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'parent_id' => ['nullable', 'integer', Rule::exists('business_categories', 'id')->where(fn ($query) => $query->whereNull('parent_id'))],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }
}
