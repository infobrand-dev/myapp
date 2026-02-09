<?php

use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WhatsAppApi\SettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::redirect('/', '/dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'role:Super-admin'])->group(function () {
    Route::resource('users', UserController::class)->except(['show']);
    Route::resource('roles', RoleController::class)->except(['show']);
});

Route::middleware(['auth', 'role:Super-admin|Admin'])
    ->prefix('whatsapp-api')
    ->name('whatsapp-api.')
    ->group(function () {
        Route::get('/settings', [SettingsController::class, 'edit'])->name('settings');
        Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('/test', [SettingsController::class, 'test'])
            ->middleware('throttle:10,1')
            ->name('test');
    });

require __DIR__ . '/auth.php';
