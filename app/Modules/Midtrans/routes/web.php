<?php

use App\Http\Middleware\VerifyCsrfToken;
use App\Modules\Midtrans\Http\Controllers\MidtransSettingsController;
use App\Modules\Midtrans\Http\Controllers\MidtransTransactionController;
use App\Modules\Midtrans\Http\Controllers\MidtransWebhookController;
use Illuminate\Support\Facades\Route;

// ─── Webhook — no auth, no CSRF ──────────────────────────────────────────────
Route::middleware(['web'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->group(function () {
        Route::post('/midtrans/webhook/notification', [MidtransWebhookController::class, 'notification'])
            ->name('midtrans.webhook.notification');
    });

// ─── Admin routes ─────────────────────────────────────────────────────────────
Route::middleware(['web', 'auth'])
    ->prefix('midtrans')
    ->name('midtrans.')
    ->group(function () {

        // Settings
        Route::get('/settings', [MidtransSettingsController::class, 'edit'])
            ->middleware('permission:midtrans.manage_settings')
            ->name('settings.edit');
        Route::put('/settings', [MidtransSettingsController::class, 'update'])
            ->middleware('permission:midtrans.manage_settings')
            ->name('settings.update');

        // Transactions
        Route::get('/transactions', [MidtransTransactionController::class, 'index'])
            ->middleware('permission:midtrans.view_transactions')
            ->name('transactions.index');
        Route::get('/transactions/{transaction}', [MidtransTransactionController::class, 'show'])
            ->middleware('permission:midtrans.view_transactions')
            ->name('transactions.show');

        // Snap token API (called by frontend JS)
        Route::post('/snap-token', [MidtransTransactionController::class, 'createSnapToken'])
            ->middleware('permission:midtrans.create_token')
            ->name('snap-token');
    });
