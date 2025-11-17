<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\admin\RBACController;
use App\Http\Controllers\ChangelogController;
use App\Http\Controllers\ExpenseIngestionController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PipedreamConnectController;
use App\Http\Controllers\Socialite\ProviderCallbackController;
use App\Http\Controllers\Socialite\ProviderRedirectController;
use App\Http\Controllers\WorkflowController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// use Spatie\Permission\Models\Role;
// $role = Role::create(['name' => 'admin']);
// $role = Role::create(['name' => 'user']);

Route::get('/', function () {
    return redirect()->route('dashboard');
})->name('home');
// Changelog
Route::get('/changelog', [ChangelogController::class, 'index'])->name('changelog');
Route::get('/privacy', [LegalController::class, 'privacy'])->name('privacy');
Route::get('/terms', [LegalController::class, 'terms'])->name('terms');

// Simple CSV upload for expense ingestion (public routes; protect if needed)
Route::get('/ingest/expenses/csv', [ExpenseIngestionController::class, 'create'])->name('expenses.ingest.form');
Route::post('/ingest/expenses/csv', [ExpenseIngestionController::class, 'store'])->name('expenses.ingest.upload');

Route::middleware(['auth', 'verified', 'onboarding'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
    // Notification routes
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->middleware('auth');
    // Mark specific notification as read
    Route::post('/notifications/{id}/mark-read', [NotificationController::class, 'markAsRead'])->middleware('auth');
    // Mark specific notification as unread
    Route::post('/notifications/{id}/mark-unread', [NotificationController::class, 'markAsUnread'])->middleware('auth');
});

// Onboarding route (accessible without onboarding middleware to avoid redirect loops)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('onboarding', function () {
        return Inertia::render('onboarding');
    })->name('onboarding');

    Route::post('onboarding/process', [OnboardingController::class, 'processCompanyInfo'])->name('onboarding.process');

    Route::post('onboarding/chat', [OnboardingController::class, 'chat'])->name('onboarding.chat');
    Route::post('onboarding/estimation', [OnboardingController::class, 'estimation'])->name('onboarding.estimation');
    Route::get('onboarding/select-plan', function () {
        return redirect()->route('onboarding');
    })->name('onboarding.select-plan.get');
    Route::post('onboarding/select-plan', [OnboardingController::class, 'selectPlan'])->name('onboarding.select-plan');
    Route::get('onboarding/check-subscription', [OnboardingController::class, 'checkSubscriptionStatus'])->name('onboarding.check-subscription');
    Route::post('onboarding/upload-financial-data', [OnboardingController::class, 'uploadFinancialData'])->name('onboarding.upload-financial-data');
    Route::get('onboarding/upload-financial-data/status/{sessionId}', [OnboardingController::class, 'getUploadStatus'])->name('onboarding.upload-financial-data.status');
    Route::post('onboarding/complete', [OnboardingController::class, 'complete'])->name('onboarding.complete');

    // Pipedream Connect routes
    Route::post('connect/token', [PipedreamConnectController::class, 'getToken'])->name('pipedream.token');
    Route::post('connect/{app}/save', [PipedreamConnectController::class, 'saveConnection'])->name('pipedream.save');
    Route::get('connect/accounts', [PipedreamConnectController::class, 'listAccounts'])->name('pipedream.accounts.list');
    Route::post('connect/{app}/request', [PipedreamConnectController::class, 'makeRequest'])->name('pipedream.request');
    Route::get('connect/{app}/callback', [PipedreamConnectController::class, 'callback'])->name('pipedream.callback');
});

if (config('features.rbac')) {
    Route::middleware(['auth', 'verified', 'onboarding', 'role:admin'])->group(function () {
        Route::get('/admin', function () {
            return redirect()->route('admin.dashboard');
        })->name('admin.home');
        Route::get('admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
        Route::get('admin/users', [AdminDashboardController::class, 'users'])->name('admin.users.index');
        Route::get('admin/users/{user}', [AdminDashboardController::class, 'showUser'])->name('admin.users.show');
        Route::put('admin/users/{user}', [AdminDashboardController::class, 'updateUser'])->name('admin.users.update');
        Route::delete('admin/users/{user}', [AdminDashboardController::class, 'deleteUser'])->name('admin.users.destroy');
        Route::post('admin/users/{user}/assign-role', [AdminDashboardController::class, 'assignRole'])->name('admin.users.assign-role');
        Route::post('admin/users/{user}/ban', [AdminDashboardController::class, 'banUser'])->name('admin.users.ban');
        Route::post('admin/users/{user}/unban', [AdminDashboardController::class, 'unbanUser'])->name('admin.users.unban');
        Route::get('admin/roles-permissions', [RBACController::class, 'index'])->name('admin.roles-permissions');
        Route::post('admin/roles', [RBACController::class, 'store'])->name('admin.roles.store');
        Route::put('admin/roles/{role}', [RBACController::class, 'update'])->name('admin.roles.update');
        Route::delete('admin/roles/{role}', [RBACController::class, 'destroy'])->name('admin.roles.destroy');
        Route::post('admin/permissions', [RBACController::class, 'storePermission'])->name('admin.permissions.store');
        Route::put('admin/permissions/{permission}', [RBACController::class, 'updatePermission'])->name('admin.permissions.update');
        Route::delete('admin/permissions/{permission}', [RBACController::class, 'destroyPermission'])->name('admin.permissions.destroy');
    });
}

Route::get('/ai/test', [WorkflowController::class, 'index'])->name('ai.workflow');
// OAuth routes
Route::get('/auth/{provider}/redirect', ProviderRedirectController::class)->name('auth.redirect')->middleware(['throttle:5,1']);
Route::get('/auth/{provider}/callback', ProviderCallbackController::class)->name('auth.callback')->middleware(['throttle:5,1']);

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/paymentRoute.php';
