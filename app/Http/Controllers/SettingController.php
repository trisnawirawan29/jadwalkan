<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        $settings = Setting::pluck('value', 'key');
        foreach (['google_client_secret', 'google_api_key'] as $secret) {
            if (! empty($settings[$secret])) {
                try {
                    $settings[$secret] = Crypt::decryptString($settings[$secret]);
                } catch (\Throwable) { /* legacy plain value */
                }
            }
        }

        return view('admin.settings', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'app_name' => ['required', 'string', 'max:60'],
            'app_tagline' => ['nullable', 'string', 'max:120'],
            'footer_text' => ['required', 'string', 'max:120'],
            'app_version' => ['required', 'string', 'max:20'],
            'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'default_theme' => ['required', 'in:light,dark'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'timezone' => ['required', 'timezone'],
            'sidebar_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'navbar_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'footer_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'google_client_id' => ['nullable', 'string', 'max:255'],
            'google_client_secret' => ['nullable', 'string', 'max:500'],
            'google_api_key' => ['nullable', 'string', 'max:500'],
            'google_redirect_uri' => ['nullable', 'url', 'max:500'],
        ]);

        $changedKeys = array_keys($data);
        foreach ($data as $key => $value) {
            if (in_array($key, ['google_client_secret', 'google_api_key'], true)) {
                continue;
            }
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        foreach (['google_client_secret', 'google_api_key'] as $secret) {
            if (filled($data[$secret] ?? null)) {
                Setting::updateOrCreate(['key' => $secret], ['value' => Crypt::encryptString($data[$secret])]);
            }
        }
        AuditLogger::record('settings.updated', 'Pengaturan aplikasi diperbarui.', null, [], ['keys' => $changedKeys]);

        return back()->with('success', 'Pengaturan aplikasi berhasil disimpan.');
    }
}
