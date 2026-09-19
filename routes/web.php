<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminBusinessCategoryController;
use App\Http\Controllers\AdminProviderApplicationController;
use App\Http\Controllers\AdminProviderPlanController;
use App\Http\Controllers\AdminProviderPlanUpgradeController;
use App\Http\Controllers\AdminUpgradeFinancialReportController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\IndonesiaRegionController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PlaceDirectoryController;
use App\Http\Controllers\ProviderApplicationController;
use App\Http\Controllers\ProviderBookingController;
use App\Http\Controllers\ProviderBusinessPlaceController;
use App\Http\Controllers\ProviderFinancialReportController;
use App\Http\Controllers\ProviderMapController;
use App\Http\Controllers\ProviderPlanController;
use App\Http\Controllers\ProviderScheduleController;
use App\Http\Controllers\ProviderServiceClosureController;
use App\Http\Controllers\ProviderServiceController;
use App\Http\Controllers\ProviderStaffController;
use App\Http\Controllers\PublicPlaceController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserManualController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingPageController::class)->name('landing');
Route::get('/places', PlaceDirectoryController::class)->name('places.index');
Route::get('/user-manual', UserManualController::class)->name('user-manual');
Route::get('/places/{businessPlace}', [PublicPlaceController::class, 'show'])->name('places.show');
Route::post('/language', [LanguageController::class, 'update'])->name('language.update');
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
    Route::get('/regions/{level}/{code?}', IndonesiaRegionController::class)->name('regions');
    Route::delete('/sessions/{sessionId}', [AccountController::class, 'revokeSession'])->name('sessions.revoke');
    Route::delete('/sessions', [AccountController::class, 'revokeOtherSessions'])->name('sessions.revoke-others');
    Route::middleware('role:user,provider')->group(function (): void {
        Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
        Route::post('/places/{businessPlace}/bookings', [BookingController::class, 'store'])->name('bookings.store');
        Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
        Route::post('/bookings/{booking}/payment-proof', [BookingController::class, 'submitPaymentProof'])->name('bookings.payment-proof');
    });
    Route::middleware('role:user')->group(function (): void {
        Route::get('/provider-application', [ProviderApplicationController::class, 'create'])->name('provider-application.create');
        Route::post('/provider-application', [ProviderApplicationController::class, 'store'])->name('provider-application.store');
    });

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index'])->name('users');
        Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::put('/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.role');
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
        Route::patch('/users/{user}/revoke-provider-plan', [AdminUserController::class, 'revokeProviderPlan'])->name('users.revoke-provider-plan');
        Route::get('/provider-applications', [AdminProviderApplicationController::class, 'index'])->name('provider-applications.index');
        Route::patch('/provider-applications/{providerApplication}/approve', [AdminProviderApplicationController::class, 'approve'])->name('provider-applications.approve');
        Route::patch('/provider-applications/{providerApplication}/reject', [AdminProviderApplicationController::class, 'reject'])->name('provider-applications.reject');
        Route::resource('provider-plans', AdminProviderPlanController::class)->except(['show'])->parameters(['provider-plans' => 'providerPlan']);
        Route::patch('/provider-plans/{providerPlan}/toggle-status', [AdminProviderPlanController::class, 'toggleStatus'])->name('provider-plans.toggle-status');
        Route::get('/provider-plan-upgrades', [AdminProviderPlanUpgradeController::class, 'index'])->name('provider-plan-upgrades.index');
        Route::get('/reports/upgrade-financial', [AdminUpgradeFinancialReportController::class, 'index'])->name('reports.upgrade-financial');
        Route::patch('/provider-plan-upgrades/{providerPlanUpgrade}/approve', [AdminProviderPlanUpgradeController::class, 'approve'])->name('provider-plan-upgrades.approve');
        Route::patch('/provider-plan-upgrades/{providerPlanUpgrade}/reject', [AdminProviderPlanUpgradeController::class, 'reject'])->name('provider-plan-upgrades.reject');
        Route::resource('business-categories', AdminBusinessCategoryController::class)->except(['show'])->parameters(['business-categories' => 'businessCategory']);
        Route::get('/settings', [SettingController::class, 'edit'])->name('settings');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs');
    });

    Route::middleware('role:provider,provider_staff')->prefix('provider')->name('provider.')->group(function (): void {
        Route::get('bookings', [ProviderBookingController::class, 'index'])->name('bookings.index');
        Route::get('bookings/scan', [ProviderBookingController::class, 'scan'])->name('bookings.scan');
        Route::patch('bookings/{booking}/approve', [ProviderBookingController::class, 'approve'])->name('bookings.approve');
        Route::patch('bookings/{booking}/reject', [ProviderBookingController::class, 'reject'])->name('bookings.reject');
        Route::get('bookings/{booking}/check-in', [ProviderBookingController::class, 'checkIn'])->middleware('signed')->name('bookings.check-in');
        Route::patch('bookings/{booking}/check-in', [ProviderBookingController::class, 'confirmAttendance'])->name('bookings.check-in.confirm');
    });

    Route::middleware('role:provider')->prefix('provider')->name('provider.')->group(function (): void {
        Route::get('plans', [ProviderPlanController::class, 'index'])->name('plans.index');
        Route::post('plans/{providerPlan}/upgrade', [ProviderPlanController::class, 'store'])->name('plans.upgrade');
        Route::get('reports/financial', [ProviderFinancialReportController::class, 'index'])->name('reports.financial');
        Route::get('staff', [ProviderStaffController::class, 'index'])->name('staff.index');
        Route::post('staff', [ProviderStaffController::class, 'store'])->name('staff.store');
        Route::delete('staff/{staff}', [ProviderStaffController::class, 'destroy'])->name('staff.destroy');
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
