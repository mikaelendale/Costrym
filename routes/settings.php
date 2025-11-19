<?php

use App\Http\Controllers\Settings\IntegrationsController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\User\BillingController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware(['social'])->group(function () {
        Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');
        Route::put('settings/password', [PasswordController::class, 'update'])
            ->middleware('throttle:6,1')
            ->name('password.update');
    });

    Route::middleware(['social.settings'])->group(function () {
        Route::get('settings/social', function () {
            return Inertia::render('settings/social');
        })->name('social');
    });

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/appearance');
    })->name('appearance');

    Route::get('settings/integrations', [IntegrationsController::class, 'index'])->name('integrations.index');

    Route::get('settings/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::post('settings/billing/change-plan', [BillingController::class, 'changePlan'])->name('billing.change-plan');
    Route::post('settings/billing/update-payment', [BillingController::class, 'updatePaymentMethod'])->name('billing.update-payment');
    Route::post('settings/billing/cancel', [BillingController::class, 'cancelSubscription'])->name('billing.cancel');
    Route::get('settings/billing/invoice/{transaction}', [BillingController::class, 'downloadInvoice'])->name('billing.download-invoice');
});
