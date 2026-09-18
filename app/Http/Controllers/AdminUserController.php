<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->when(request('search'), fn ($query, $search) => $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->when(request('role'), fn ($query, $role) => $query->where('role', $role))
            ->latest()
            ->get();

        return view('admin.users', compact('users'));
    }

    public function create(): View
    {
        return view('admin.user-form', ['user' => new User, 'pageTitle' => 'Tambah Pengguna']);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());
        $user = User::create($data);
        AuditLogger::record('user.created', "Pengguna {$user->email} dibuat.", $user, [], $user->only(['name', 'email', 'role']));

        return redirect()->route('admin.users')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(User $user): View
    {
        return view('admin.user-form', ['user' => $user, 'pageTitle' => 'Edit Pengguna']);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate($this->rules($user));
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }
        $user->update($data);
        AuditLogger::record('user.updated', "Data pengguna {$user->email} diperbarui.", $user, $user->getOriginal(), $user->only(['name', 'email', 'role']));

        return redirect()->route('admin.users')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['role' => ['required', Rule::in(['admin', 'manager', 'user'])]]);

        if ($user->is(auth()->user()) && $data['role'] !== 'admin') {
            return back()->withErrors(['role' => 'Anda tidak dapat menghapus role admin dari akun sendiri.']);
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

    private function rules(?User $user = null): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user?->id)],
            'role' => ['required', Rule::in(['admin', 'manager', 'user'])],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::min(8)],
        ];
    }
}
