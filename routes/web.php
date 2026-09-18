<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminBusinessCategoryController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\IndonesiaRegionController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProviderBusinessPlaceController;
use App\Http\Controllers\ProviderMapController;
use App\Http\Controllers\ProviderScheduleController;
use App\Http\Controllers\ProviderServiceClosureController;
use App\Http\Controllers\ProviderServiceController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.store');
Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::post('/language', [LanguageController::class, 'update'])->name('language.update');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::patch('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('/profile', [AccountController::class, 'profile'])->name('profile');
    Route::put('/profile', [AccountController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/avatar', [AccountController::class, 'updateAvatar'])->name('profile.avatar');
    Route::get('/change-password', [AccountController::class, 'password'])->name('password.change');
    Route::put('/change-password', [AccountController::class, 'updatePassword'])->name('password.change.update');
    Route::get('/sessions', [AccountController::class, 'sessions'])->name('sessions');
    Route::delete('/sessions/{sessionId}', [AccountController::class, 'revokeSession'])->name('sessions.revoke');
    Route::delete('/sessions', [AccountController::class, 'revokeOtherSessions'])->name('sessions.revoke-others');

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index'])->name('users');
        Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::put('/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.role');
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
        Route::resource('business-categories', AdminBusinessCategoryController::class)->except(['show'])->parameters(['business-categories' => 'businessCategory']);
        Route::get('/settings', [SettingController::class, 'edit'])->name('settings');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs');
    });

    Route::middleware('role:provider')->prefix('provider')->name('provider.')->group(function () {
        Route::get('regions/{level}/{code?}', IndonesiaRegionController::class)->name('regions');
        Route::get('map/geocode', [ProviderMapController::class, 'geocode'])->name('map.geocode');
        Route::patch('business-places/{businessPlace}/status', [ProviderBusinessPlaceController::class, 'toggleStatus'])->name('business-places.status');
        Route::resource('business-places', ProviderBusinessPlaceController::class);
        Route::resource('business-places.services', ProviderServiceController::class)->parameters(['services' => 'businessService']);
        Route::post('business-places/{businessPlace}/services/{businessService}/closures', [ProviderServiceClosureController::class, 'store'])->name('business-places.services.closures.store');
        Route::delete('business-places/{businessPlace}/services/{businessService}/closures/{serviceClosure}', [ProviderServiceClosureController::class, 'destroy'])->name('business-places.services.closures.destroy');
        Route::resource('business-places.services.schedules', ProviderScheduleController::class)->parameters(['services' => 'businessService']);
    });
});
