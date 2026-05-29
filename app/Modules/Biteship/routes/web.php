<?php

use App\Modules\Biteship\Http\Controllers\BiteshipSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('biteship')
    ->name('biteship.')
    ->group(function (): void {
        Route::get('/settings', [BiteshipSettingsController::class, 'edit'])
            ->middleware('permission:biteship.manage_settings')
            ->name('settings.edit');
        Route::put('/settings', [BiteshipSettingsController::class, 'update'])
            ->middleware('permission:biteship.manage_settings')
            ->name('settings.update');
    });
