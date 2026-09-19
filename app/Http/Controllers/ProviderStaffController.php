<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProviderStaffController extends Controller
{
    public function index(Request $request): View
    {
        $staffMembers = $request->user()->staffMembers()->latest()->get();

        return view('provider.staff.index', compact('staffMembers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->user()->staffMembers()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'provider_staff',
        ]);

        return back()->with('success', 'Pegawai provider berhasil didaftarkan.');
    }

    public function destroy(Request $request, User $staff): RedirectResponse
    {
        abort_unless($staff->isProviderStaff() && $staff->provider_id === $request->user()->id, 404);

        $staff->delete();

        return back()->with('success', 'Akses pegawai berhasil dicabut.');
    }
}
