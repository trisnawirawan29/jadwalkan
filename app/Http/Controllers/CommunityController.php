<?php

namespace App\Http\Controllers;

use App\Models\BusinessCategory;
use App\Models\SportCommunity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommunityController extends Controller
{
    public function index(Request $request): View
    {
        $request->user()->loadMissing('sportInterests');
        $sports = BusinessCategory::query()->where('is_active', true)->whereNotNull('parent_id')->with(['sportCommunity' => fn ($query) => $query->withCount('members')])->withCount('interestedUsers')->orderBy('sort_order')->orderBy('name')->get();

        return view('communities.index', compact('sports'));
    }

    public function join(Request $request, BusinessCategory $businessCategory): RedirectResponse
    {
        abort_unless($businessCategory->is_active && $businessCategory->parent_id !== null, 404);
        $community = SportCommunity::query()->firstOrCreate(['business_category_id' => $businessCategory->id], ['name' => 'Komunitas '.$businessCategory->name, 'description' => 'Temukan teman latihan dan buat kegiatan '.$businessCategory->name.' bersama.', 'is_active' => true]);
        $user = $request->user();

        if ($user->sportInterests()->whereKey($businessCategory->id)->exists()) {
            $user->sportInterests()->detach($businessCategory->id);
            $community->members()->detach($user->id);

            return back()->with('success', "Anda keluar dari komunitas {$businessCategory->name}.");
        }

        $user->sportInterests()->syncWithoutDetaching([$businessCategory->id]);
        $community->members()->syncWithoutDetaching([$user->id => ['status' => 'active']]);

        return back()->with('success', "Anda bergabung ke komunitas {$businessCategory->name}.");
    }
}
