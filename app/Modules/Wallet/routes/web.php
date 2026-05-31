<?php

use App\Modules\Wallet\Http\Controllers\PlatformWalletPayoutController;
use App\Modules\Wallet\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'plan.feature:commerce', 'permission:wallet.view'])
    ->prefix('wallet')
    ->name('wallet.')
    ->group(function () {
        Route::get('/', [WalletController::class, 'index'])->name('index');
        Route::post('/payout-requests', [WalletController::class, 'storePayoutRequest'])
            ->middleware('permission:wallet.manage')
            ->name('payout-requests.store');
    });

Route::middleware(['web', 'auth', 'platform.admin', 'permission:wallet.payouts.review'])
    ->prefix('platform/wallet')
    ->name('platform.wallet.')
    ->group(function () {
        Route::get('/payouts', [PlatformWalletPayoutController::class, 'index'])->name('payouts.index');
        Route::post('/payouts/{payoutRequest}/approve', [PlatformWalletPayoutController::class, 'approve'])->name('payouts.approve');
        Route::post('/payouts/{payoutRequest}/mark-paid', [PlatformWalletPayoutController::class, 'markPaid'])->name('payouts.mark-paid');
        Route::post('/payouts/{payoutRequest}/reject', [PlatformWalletPayoutController::class, 'reject'])->name('payouts.reject');
    });
