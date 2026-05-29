<?php

use App\Http\Middleware\VerifyCsrfToken;
use App\Modules\Xendit\Http\Controllers\XenditSettingsController;
use App\Modules\Xendit\Http\Controllers\XenditTransactionController;
use App\Modules\Xendit\Http\Controllers\XenditWebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->group(function (): void {
        Route::post('/xendit/webhook/notification', [XenditWebhookController::class, 'notification'])
            ->name('xendit.webhook.notification');
    });

Route::middleware(['web', 'auth'])
    ->prefix('xendit')
    ->name('xendit.')
    ->group(function (): void {
        Route::get('/settings', [XenditSettingsController::class, 'edit'])
            ->middleware('permission:xendit.manage_settings')
            ->name('settings.edit');
        Route::put('/settings', [XenditSettingsController::class, 'update'])
            ->middleware('permission:xendit.manage_settings')
            ->name('settings.update');

        Route::get('/transactions', [XenditTransactionController::class, 'index'])
            ->middleware('permission:xendit.view_transactions')
            ->name('transactions.index');
        Route::get('/transactions/{transaction}', [XenditTransactionController::class, 'show'])
            ->middleware('permission:xendit.view_transactions')
            ->name('transactions.show');
    });
