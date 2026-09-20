<?php

namespace App\Http\Controllers;

use App\Models\ProviderPlan;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->with('providerPlan')
            ->when(request('search'), fn ($query, $search) => $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->when(request('role'), fn ($query, $role) => $query->where('role', $role))
            ->latest()
            ->get();

        return view('admin.users', compact('users'));
    }

    public function create(): View
    {
        return view('admin.user-form', ['user' => new User, 'pageTitle' => 'Tambah Pengguna', 'providerPlans' => ProviderPlan::query()->where('is_active', true)->orderBy('monthly_price')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());
        if ($data['role'] === 'provider') {
            $freePlan = ProviderPlan::query()->where('is_free', true)->firstOrFail();
            $data['provider_plan_id'] = $freePlan->id;
            $data['provider_plan_started_at'] = now();
        } else {
            $data['provider_plan_id'] = null;
        }
        $user = User::create($data);
        AuditLogger::record('user.created', "Pengguna {$user->email} dibuat.", $user, [], $user->only(['name', 'email', 'role']));

        return redirect()->route('admin.users')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(User $user): View
    {
        return view('admin.user-form', ['user' => $user, 'pageTitle' => 'Edit Pengguna', 'providerPlans' => ProviderPlan::query()->where('is_active', true)->orderBy('monthly_price')->get()]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate($this->rules($user));
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }
        if ($data['role'] === 'provider' && ! $user->isProvider()) {
            $freePlan = ProviderPlan::query()->where('is_free', true)->firstOrFail();
            $data['provider_plan_id'] = $freePlan->id;
            $data['provider_plan_started_at'] = now();
            $data['provider_plan_expires_at'] = null;
        } elseif ($data['role'] !== 'provider') {
            $data['provider_plan_id'] = null;
            $data['provider_plan_started_at'] = null;
            $data['provider_plan_expires_at'] = null;
        }
        $user->update($data);
        AuditLogger::record('user.updated', "Data pengguna {$user->email} diperbarui.", $user, $user->getOriginal(), $user->only(['name', 'email', 'role']));

        return redirect()->route('admin.users')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['role' => ['required', Rule::in(['admin', 'manager', 'provider', 'user', 'superadmin'])]]);

        if ($user->is(auth()->user()) && $data['role'] !== 'admin') {
            return back()->withErrors(['role' => 'Anda tidak dapat menghapus role admin dari akun sendiri.']);
        }

        if ($data['role'] === 'provider' && ! $user->provider_plan_id) {
            $freePlan = ProviderPlan::query()->where('is_free', true)->firstOrFail();
            $data['provider_plan_id'] = $freePlan->id;
            $data['provider_plan_started_at'] = now();
            $data['provider_plan_expires_at'] = null;
        } elseif ($data['role'] !== 'provider') {
            $data['provider_plan_id'] = null;
            $data['provider_plan_started_at'] = null;
            $data['provider_plan_expires_at'] = null;
        }

        $user->update($data);
        AuditLogger::record('user.role_changed', "Role {$user->email} diubah menjadi {$data['role']}.", $user, ['role' => $user->getOriginal('role')], ['role' => $data['role']]);

        return back()->with('success', 'Role pengguna berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->is(auth()->user())) {
            return back()->withErrors(['user' => 'Anda tidak dapat menghapus akun sendiri.']);
        }

        if ($user->isAdmin() && User::where('role', 'admin')->count() <= 1) {
            return back()->withErrors(['user' => 'Akun admin terakhir tidak dapat dihapus.']);
        }

        $email = $user->email;
        AuditLogger::record('user.deleted', "Pengguna {$email} dihapus.", $user, $user->only(['name', 'email', 'role']));
        $user->delete();

        return back()->with('success', 'Pengguna berhasil dihapus.');
    }

    public function revokeProviderPlan(User $user): RedirectResponse
    {
        if (! $user->isProvider() || ! $user->provider_plan_id) {
            return back()->withErrors(['provider_plan' => 'Pengguna ini tidak memiliki paket provider aktif.']);
        }

        $planName = $user->providerPlan?->name ?: 'provider';
        $freePlan = ProviderPlan::query()->where('is_free', true)->firstOrFail();

        DB::transaction(function () use ($user, $freePlan): void {
            $user->update([
                'provider_plan_id' => $freePlan->id,
                'provider_plan_started_at' => now(),
                'provider_plan_expires_at' => null,
            ]);
            $user->businessPlaces()->update(['is_active' => false]);
        });
        AuditLogger::record('provider_plan.revoked', "Paket {$planName} dicabut dari provider {$user->email}.", $user, [], ['provider_plan_id' => null]);

        return back()->with('success', "Paket {$planName} dari {$user->name} berhasil diubah ke paket Free. Bisnis provider dinonaktifkan.");
    }

    private function rules(?User $user = null): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user?->id)],
            'role' => ['required', Rule::in(['admin', 'manager', 'provider', 'user', 'superadmin'])],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::min(8)],
        ];
    }
}
