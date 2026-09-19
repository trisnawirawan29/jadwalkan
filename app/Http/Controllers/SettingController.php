<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
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
            'provider_plan_bank_name' => ['nullable', 'string', 'max:100'],
            'provider_plan_bank_account_name' => ['nullable', 'string', 'max:150'],
            'provider_plan_bank_account_number' => ['nullable', 'string', 'max:50'],
            'provider_plan_qris_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $changedKeys = array_keys($data);
        foreach ($data as $key => $value) {
            if (in_array($key, ['google_client_secret', 'google_api_key', 'provider_plan_qris_image'], true)) {
                continue;
            }
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        foreach (['google_client_secret', 'google_api_key'] as $secret) {
            if (filled($data[$secret] ?? null)) {
                Setting::updateOrCreate(['key' => $secret], ['value' => Crypt::encryptString($data[$secret])]);
            }
        }
        if ($request->hasFile('provider_plan_qris_image')) {
            $previousPath = Setting::query()->where('key', 'provider_plan_qris_image')->value('value');
            $qrisPath = $request->file('provider_plan_qris_image')->store('provider-plan-payment', 'public');
            Setting::updateOrCreate(['key' => 'provider_plan_qris_image'], ['value' => $qrisPath]);

            if ($previousPath) {
                Storage::disk('public')->delete($previousPath);
            }
        }
        AuditLogger::record('settings.updated', 'Pengaturan aplikasi diperbarui.', null, [], ['keys' => $changedKeys]);

        return back()->with('success', 'Pengaturan aplikasi berhasil disimpan.');
    }
}
