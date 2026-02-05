<?php

use App\Modules\Shortlink\Http\Controllers\ShortlinkController;
use App\Modules\Shortlink\Http\Controllers\ShortlinkRedirectController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])
    ->get('/r/{code}', [ShortlinkRedirectController::class, 'show'])
    ->name('shortlinks.redirect');

Route::middleware(['web', 'auth', 'role:Super-admin|Admin'])
    ->prefix('shortlinks')
    ->name('shortlinks.')
    ->group(function () {
        Route::get('/', [ShortlinkController::class, 'index'])->name('index');
        Route::get('/create', [ShortlinkController::class, 'create'])->name('create');
        Route::post('/', [ShortlinkController::class, 'store'])->name('store');
        Route::get('/{shortlink}/edit', [ShortlinkController::class, 'edit'])->name('edit');
        Route::put('/{shortlink}', [ShortlinkController::class, 'update'])->name('update');
    });
