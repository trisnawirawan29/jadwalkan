<?php

namespace App\Http\Controllers;

use App\Models\ProviderPaymentMethod;
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
    private const COMMON_BANKS = [
        'Bank Central Asia (BCA)',
        'Bank Rakyat Indonesia (BRI)',
        'Bank Mandiri',
        'Bank Negara Indonesia (BNI)',
        'Bank Tabungan Negara (BTN)',
        'CIMB Niaga',
        'Bank Danamon',
        'Bank Permata',
        'OCBC Indonesia',
        'Maybank Indonesia',
        'Bank Panin',
        'Bank Syariah Indonesia (BSI)',
        'Bank Mega',
        'Bank Jago',
        'SeaBank Indonesia',
        'Jenius',
        'Lainnya',
    ];

    public function profile(): View
    {
        $sessions = DB::table('sessions')->where('user_id', auth()->id())->orderByDesc('last_activity')->get();

        return view('account.profile', compact('sessions'));
    }

    public function payment(): View
    {
        return view('account.payment', [
            'commonBanks' => self::COMMON_BANKS,
            'paymentMethods' => auth()->user()->paymentMethods()->latest()->get(),
        ]);
    }

    public function storePaymentMethod(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['bank_transfer', 'qris'])],
            'bank_name' => ['required_if:type,bank_transfer', 'nullable', 'string', 'max:100'],
            'account_name' => ['required_if:type,bank_transfer', 'nullable', 'string', 'max:150'],
            'account_number' => ['required_if:type,bank_transfer', 'nullable', 'string', 'max:50'],
            'qris_image' => ['required_if:type,qris', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if ($request->hasFile('qris_image')) {
            $data['qris_image'] = $request->file('qris_image')->store('payment-qris', 'public');
        }

        $request->user()->paymentMethods()->create([
            ...$data,
            'is_active' => true,
        ]);

        return back()->with('success', 'Metode pembayaran berhasil ditambahkan.');
    }

    public function togglePaymentMethod(Request $request, ProviderPaymentMethod $paymentMethod): RedirectResponse
    {
        abort_unless($paymentMethod->provider_id === $request->user()->id, 403);

        $paymentMethod->update(['is_active' => ! $paymentMethod->is_active]);

        return back()->with('success', $paymentMethod->is_active
            ? 'Metode pembayaran diaktifkan.'
            : 'Metode pembayaran dinonaktifkan.');
    }

    public function destroyPaymentMethod(Request $request, ProviderPaymentMethod $paymentMethod): RedirectResponse
    {
        abort_unless($paymentMethod->provider_id === $request->user()->id, 403);

        if ($paymentMethod->qris_image) {
            Storage::disk('public')->delete($paymentMethod->qris_image);
        }

        $paymentMethod->delete();

        return back()->with('success', 'Metode pembayaran berhasil dihapus.');
    }

    public function updatePayment(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate($this->paymentRules());

        if ($request->hasFile('payment_qris_image')) {
            if ($user->payment_qris_image) {
                Storage::disk('public')->delete($user->payment_qris_image);
            }
            $data['payment_qris_image'] = $request->file('payment_qris_image')->store('payment-qris', 'public');
        }

        $old = $user->only(array_keys($data));
        $user->update($data);
        AuditLogger::record('profile.payment_updated', 'Informasi pembayaran provider diperbarui.', $user, $old, $user->only(array_keys($data)));

        return back()->with('success', 'Informasi pembayaran berhasil disimpan.');
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'job_title' => ['nullable', 'string', 'max:100'], 'phone' => ['nullable', 'string', 'max:30'],
            'location' => ['nullable', 'string', 'max:100'], 'birth_date' => ['nullable', 'date'],
            'province_code' => ['nullable', 'string', 'max:10'], 'province_name' => ['nullable', 'string', 'max:100'],
            'regency_code' => ['nullable', 'string', 'max:15'], 'regency_name' => ['nullable', 'string', 'max:100'],
            'district_code' => ['nullable', 'string', 'max:20'], 'district_name' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'url', 'max:255'], 'bio' => ['nullable', 'string', 'max:1000'],
            ...$this->paymentRules(),
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

    private function paymentRules(): array
    {
        return [
            'payment_bank_name' => ['nullable', 'string', 'max:100'],
            'payment_bank_account_name' => ['nullable', 'string', 'max:150'],
            'payment_bank_account_number' => ['nullable', 'string', 'max:50'],
            'payment_qris_image' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
