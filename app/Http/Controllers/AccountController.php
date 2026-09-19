<?php

namespace App\Http\Controllers;

use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function profile(): View
    {
        $sessions = DB::table('sessions')->where('user_id', auth()->id())->orderByDesc('last_activity')->get();

        return view('account.profile', compact('sessions'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'job_title' => ['nullable', 'string', 'max:100'], 'phone' => ['nullable', 'string', 'max:30'],
            'location' => ['nullable', 'string', 'max:100'], 'birth_date' => ['nullable', 'date'],
            'website' => ['nullable', 'url', 'max:255'], 'bio' => ['nullable', 'string', 'max:1000'],
            'payment_bank_name' => ['nullable', 'string', 'max:100'],
            'payment_bank_account_name' => ['nullable', 'string', 'max:150'],
            'payment_bank_account_number' => ['nullable', 'string', 'max:50'],
            'payment_qris_image' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        if ($request->hasFile('payment_qris_image')) {
            if ($user->payment_qris_image) {
                Storage::disk('public')->delete($user->payment_qris_image);
            }
            $data['payment_qris_image'] = $request->file('payment_qris_image')->store('payment-qris', 'public');
        }
        $old = $user->only(array_keys($data));
        $user->update($data);
        AuditLogger::record('profile.updated', 'Profil pengguna diperbarui.', $user, $old, $user->only(array_keys($data)));

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validate(['avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048']]);
        $user = $request->user();
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }
        $avatar = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar' => $avatar]);
        AuditLogger::record('profile.avatar_updated', 'Avatar pengguna diperbarui.', $user, [], ['avatar' => $avatar]);

        return back()->with('success', 'Avatar berhasil diperbarui.');
    }

    public function password(): View
    {
        return view('account.password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate(['current_password' => ['required'], 'password' => ['required', 'confirmed', 'min:8']]);
        if (! Hash::check($data['current_password'], $request->user()->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini tidak sesuai.']);
        }
        $request->user()->update(['password' => $data['password']]);
        Auth::logoutOtherDevices($data['password']);
        AuditLogger::record('security.password_changed', 'Password pengguna berhasil diubah.', $request->user());

        return back()->with('success', 'Password berhasil diubah. Sesi di perangkat lain telah dihentikan.');
    }

    public function sessions(Request $request): View
    {
        $sessions = DB::table('sessions')->where('user_id', $request->user()->id)->orderByDesc('last_activity')->get();

        return view('account.sessions', compact('sessions'));
    }

    public function revokeSession(Request $request, string $sessionId): RedirectResponse
    {
        if ($sessionId === $request->session()->getId()) {
            return back()->with('warning', 'Sesi yang sedang digunakan tidak dapat dihentikan dari halaman ini.');
        }

        DB::table('sessions')->where('id', $sessionId)->where('user_id', $request->user()->id)->delete();
        AuditLogger::record('security.session_revoked', 'Satu sesi perangkat dihentikan.', $request->user(), ['session_id' => $sessionId]);

        return back()->with('success', 'Sesi berhasil dihentikan.');
    }

    public function revokeOtherSessions(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required']]);
        if (! Hash::check($request->password, $request->user()->password)) {
            return back()->withErrors(['password' => 'Password tidak sesuai.']);
        }
        DB::table('sessions')->where('user_id', $request->user()->id)->where('id', '!=', $request->session()->getId())->delete();
        AuditLogger::record('security.sessions_revoked', 'Semua sesi perangkat lain dihentikan.', $request->user());

        return back()->with('success', 'Semua sesi pada perangkat lain telah dihentikan.');
    }
}
