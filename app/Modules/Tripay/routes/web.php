<?php

use App\Http\Middleware\VerifyCsrfToken;
use App\Modules\Tripay\Http\Controllers\TripaySettingsController;
use App\Modules\Tripay\Http\Controllers\TripayTransactionController;
use App\Modules\Tripay\Http\Controllers\TripayWebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->group(function (): void {
        Route::post('/tripay/webhook/notification', [TripayWebhookController::class, 'notification'])
            ->middleware('throttle:public-webhook')
            ->name('tripay.webhook.notification');
    });

Route::middleware(['web', 'auth'])
    ->prefix('tripay')
    ->name('tripay.')
    ->group(function (): void {
        Route::get('/settings', [TripaySettingsController::class, 'edit'])
            ->middleware('permission:tripay.manage_settings')
            ->name('settings.edit');
        Route::put('/settings', [TripaySettingsController::class, 'update'])
            ->middleware('permission:tripay.manage_settings')
            ->name('settings.update');

        Route::get('/transactions', [TripayTransactionController::class, 'index'])
            ->middleware('permission:tripay.view_transactions')
            ->name('transactions.index');
        Route::get('/transactions/{transaction}', [TripayTransactionController::class, 'show'])
            ->middleware('permission:tripay.view_transactions')
            ->name('transactions.show');
    });
