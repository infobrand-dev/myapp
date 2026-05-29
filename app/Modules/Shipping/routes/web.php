<?php

use App\Modules\Shipping\Http\Controllers\ShippingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'plan.feature:commerce', 'plan.feature:shipping', 'permission:shipping.view'])
    ->prefix('shipping')
    ->name('shipping.')
    ->group(function () {
        Route::get('/', ShippingController::class)->name('index');
        Route::post('/bulk', [ShippingController::class, 'bulkRate'])
            ->middleware('permission:shipping.manage')
            ->name('bulk');
        Route::post('/quote', [ShippingController::class, 'quote'])
            ->middleware('permission:shipping.manage')
            ->name('quote');
        Route::post('/{sale}/rate', [ShippingController::class, 'selectRate'])
            ->middleware('permission:shipping.manage')
            ->name('rate');
        Route::post('/{sale}/ship', [ShippingController::class, 'ship'])
            ->middleware('permission:shipping.manage')
            ->name('ship');
    });
