<?php

use App\Modules\Sales\Http\Controllers\Api\SaleChannelController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'auth:sanctum'])
    ->prefix('api/sales')
    ->name('sales.api.')
    ->group(function () {
        Route::post('/channel-transactions', [SaleChannelController::class, 'store'])
            ->middleware('permission:sales.create')
            ->name('channel-transactions.store');

        Route::post('/{sale}/finalize', [SaleChannelController::class, 'finalize'])
            ->middleware('permission:sales.finalize')
            ->name('finalize');
    });
