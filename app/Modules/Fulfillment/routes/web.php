<?php

use App\Modules\Fulfillment\Http\Controllers\FulfillmentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'plan.feature:commerce', 'plan.feature:fulfillment', 'permission:fulfillment.view'])
    ->prefix('fulfillment')
    ->name('fulfillment.')
    ->group(function () {
        Route::get('/', FulfillmentController::class)->name('index');
        Route::post('/bulk', [FulfillmentController::class, 'bulkUpdate'])
            ->middleware('permission:fulfillment.manage')
            ->name('bulk');
        Route::post('/{sale}/packing', [FulfillmentController::class, 'markPacking'])
            ->middleware('permission:fulfillment.manage')
            ->name('packing');
        Route::post('/{sale}/ready', [FulfillmentController::class, 'markReady'])
            ->middleware('permission:fulfillment.manage')
            ->name('ready');
    });
