<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        $clientId = Setting::value('google_client_id');
        $redirectUri = Setting::value('google_redirect_uri') ?: route('auth.google.callback');

        if (! $clientId || ! $this->secret('google_client_secret')) {
            return redirect()->route('login')->withErrors(['email' => 'Login Google belum dikonfigurasi oleh administrator.']);
        }

        $state = Str::random(40);
        session(['google_oauth_state' => $state]);
        $query = http_build_query(['client_id' => $clientId, 'redirect_uri' => $redirectUri, 'response_type' => 'code', 'scope' => 'openid email profile', 'state' => $state, 'access_type' => 'online', 'prompt' => 'select_account']);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.$query);
    }

    public function callback(Request $request): RedirectResponse
    {
        if (! $request->filled('code') || ! hash_equals((string) session('google_oauth_state'), (string) $request->input('state'))) {
            return redirect()->route('login')->withErrors(['email' => 'Sesi login Google tidak valid atau sudah kedaluwarsa.']);
        }

        $redirectUri = Setting::value('google_redirect_uri') ?: route('auth.google.callback');
        $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', ['code' => $request->code, 'client_id' => Setting::value('google_client_id'), 'client_secret' => $this->secret('google_client_secret'), 'redirect_uri' => $redirectUri, 'grant_type' => 'authorization_code']);
        if ($tokenResponse->failed()) {
            return redirect()->route('login')->withErrors(['email' => 'Google gagal memverifikasi akun Anda.']);
        }

        $googleUser = Http::withToken($tokenResponse->json('access_token'))->get('https://openidconnect.googleapis.com/v1/userinfo');
        if ($googleUser->failed() || ! $googleUser->json('email')) {
            return redirect()->route('login')->withErrors(['email' => 'Data akun Google tidak dapat dibaca.']);
        }

        $profile = $googleUser->json();
        $user = User::firstOrCreate(['email' => $profile['email']], ['name' => $profile['name'] ?? Str::before($profile['email'], '@'), 'password' => Str::random(40), 'role' => 'user']);
        if ($user->wasRecentlyCreated) {
            AuditLogger::record('user.created', "Pengguna Google {$user->email} dibuat.", $user, [], $user->only(['name', 'email', 'role']), $request);
        }
        $user->forceFill(['google_id' => $profile['sub'], 'name' => $profile['name'] ?? $user->name])->save();
        AuditLogger::record('auth.google_login', 'Pengguna berhasil login dengan Google.', $user, [], ['provider' => 'google'], $request);
        Auth::login($user, true);
        $request->session()->regenerate();
        $request->session()->forget('google_oauth_state');

        return redirect()->intended(route('dashboard'))->with('success', 'Berhasil masuk menggunakan akun Google.');
    }

    private function secret(string $key): ?string
    {
        $value = Setting::value($key);
        if (! $value) {
            return null;
        }
        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return $value;
        }
    }
}
