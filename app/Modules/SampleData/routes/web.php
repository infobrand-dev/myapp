<?php

use App\Modules\SampleData\Http\Controllers\SampleDataController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'permission:sample-data.view'])
    ->prefix('sample-data')
    ->name('sample-data.')
    ->group(function () {
        Route::get('/', [SampleDataController::class, 'index'])->name('index');
        Route::post('/{slug}', [SampleDataController::class, 'store'])
            ->middleware('permission:sample-data.run')
            ->name('store');
    });
