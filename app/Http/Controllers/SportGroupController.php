<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSportGroupRequest;
use App\Models\BusinessCategory;
use App\Models\SportGroup;
use App\Models\User;
use App\Services\SportGroupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SportGroupController extends Controller
{
    public function index(Request $request, SportGroupService $groupService): View
    {
        $validated = $request->validate([
            'sport' => ['nullable', 'integer', Rule::exists('business_categories', 'id')->where(fn ($query) => $query->where('is_active', true)->whereNotNull('parent_id'))],
        ]);
        $sports = BusinessCategory::query()->where('is_active', true)->whereNotNull('parent_id')->orderBy('sort_order')->orderBy('name')->get();
        $selectedSportId = $validated['sport'] ?? null;
        $groups = $request->user()->sportGroups()->with(['businessCategory', 'creator', 'selectedBusinessService.businessPlace'])->withCount(['members as accepted_members_count' => fn ($query) => $query->where('sport_group_user.status', 'accepted')])->orderBy('activity_date')->orderBy('start_time')->paginate(10);
        $suggestions = $groupService->matchingGroupsFor($request->user());
        $popularSchedules = $groupService->popularSchedulesAround($request->user(), $selectedSportId);

        return view('sport-groups.index', compact('groups', 'suggestions', 'popularSchedules', 'sports', 'selectedSportId'));
    }

    public function create(): View
    {
        $sports = BusinessCategory::query()->where('is_active', true)->whereNotNull('parent_id')->orderBy('sort_order')->orderBy('name')->get();

        return view('sport-groups.create', compact('sports'));
    }

    public function store(StoreSportGroupRequest $request, SportGroupService $groupService): RedirectResponse
    {
        $request->user()->sportInterests()->syncWithoutDetaching([$request->integer('business_category_id')]);
        $data = $request->validated();
        $group = SportGroup::create([...$data, 'level' => $data['level'] ?? 'beginner', 'creator_id' => $request->user()->id]);
        $group->members()->attach($request->user()->id, ['status' => 'accepted', 'responded_at' => now()]);
        $group->load(['businessCategory', 'members']);
        if (! $groupService->assignBestAvailablePlace($group)) {
            $group->delete();

            return back()->withErrors(['activity_date' => 'Belum ada lapangan yang tersedia pada tanggal dan jam tersebut.'])->withInput();
        }

        return redirect()->route('sport-groups.show', $group)->with('success', 'Kelompok dibuat dengan lapangan yang tersedia. User yang cocok dapat menemukan dan bergabung dari menu Kelompok olahraga.');
    }

    public function show(SportGroup $sportGroup, Request $request, SportGroupService $groupService): View
    {
        $this->ensureMember($sportGroup, $request->user());
        $sportGroup->load(['businessCategory', 'creator', 'members' => fn ($query) => $query->orderBy('name')]);
        $recommendations = $groupService->recommendations($sportGroup)->take(5);
        $recommendationDistances = $recommendations->mapWithKeys(fn ($service): array => [$service->id => $groupService->averageDistance($service, $sportGroup->members)])->all();

        return view('sport-groups.show', compact('sportGroup', 'recommendations', 'recommendationDistances'));
    }

    public function respond(Request $request, SportGroup $sportGroup, SportGroupService $groupService): RedirectResponse
    {
        $status = $request->validate(['status' => ['required', 'in:accepted,declined']])['status'];
        abort_unless($sportGroup->members()->whereKey($request->user()->id)->exists(), 403);
        $sportGroup->members()->updateExistingPivot($request->user()->id, ['status' => $status, 'responded_at' => now()]);

        if ($status === 'declined') {
            $sportGroup->load('businessCategory');
            $groupService->inviteMatchingUsers($sportGroup, $sportGroup->max_members);
        }

        return back()->with('success', $status === 'accepted' ? 'Konfirmasi ikut telah disimpan.' : 'Slot Anda ditawarkan ke pengguna lain dengan minat olahraga yang sama.');
    }

    public function join(Request $request, SportGroup $sportGroup, SportGroupService $groupService): RedirectResponse
    {
        $groupService->joinGroup($request->user(), $sportGroup);

        return redirect()->route('sport-groups.show', $sportGroup)->with('success', 'Anda berhasil bergabung ke kelompok olahraga.');
    }

    public function selectPlace(Request $request, SportGroup $sportGroup, SportGroupService $groupService): RedirectResponse
    {
        $this->ensureMember($sportGroup, $request->user());
        $serviceId = $request->validate(['business_service_id' => ['required', 'integer']])['business_service_id'];
        abort_unless($groupService->recommendations($sportGroup)->contains('id', $serviceId), 422, 'Lapangan tidak tersedia pada jadwal kegiatan ini.');
        $sportGroup->update(['selected_business_service_id' => $serviceId]);

        return back()->with('success', 'Rekomendasi lapangan dipilih untuk kelompok.');
    }

    private function ensureMember(SportGroup $sportGroup, User $user): void
    {
        abort_unless($sportGroup->members()->whereKey($user->id)->exists(), 403);
    }
}
