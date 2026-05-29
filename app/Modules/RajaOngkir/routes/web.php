<?php

use App\Modules\RajaOngkir\Http\Controllers\RajaOngkirSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('rajaongkir')
    ->name('rajaongkir.')
    ->group(function (): void {
        Route::get('/settings', [RajaOngkirSettingsController::class, 'edit'])
            ->middleware('permission:rajaongkir.manage_settings')
            ->name('settings.edit');
        Route::put('/settings', [RajaOngkirSettingsController::class, 'update'])
            ->middleware('permission:rajaongkir.manage_settings')
            ->name('settings.update');
    });
