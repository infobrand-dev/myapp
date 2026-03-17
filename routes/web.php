<?php

use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\UserPresenceController;
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

Route::withoutMiddleware([
    \App\Http\Middleware\EncryptCookies::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \App\Http\Middleware\VerifyCsrfToken::class,
])->group(function () {
    Route::get('/install', [InstallController::class, 'index'])->name('install.index');
    Route::post('/install/test-db', [InstallController::class, 'testDatabase'])->name('install.test-db');
    Route::post('/install/run', [InstallController::class, 'run'])->name('install.run');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth'])->prefix('presence')->name('presence.')->group(function () {
    Route::post('/heartbeat', [UserPresenceController::class, 'heartbeat'])->name('heartbeat');
    Route::post('/status', [UserPresenceController::class, 'setStatus'])->name('status');
});

Route::middleware(['auth', 'role:Super-admin'])->group(function () {
    Route::resource('users', UserController::class)->except(['show']);
    Route::resource('roles', RoleController::class)->except(['show']);
    Route::get('/modules', [ModuleController::class, 'index'])->name('modules.index');
    Route::post('/modules/{slug}/install', [ModuleController::class, 'install'])->name('modules.install');
    Route::post('/modules/{slug}/activate', [ModuleController::class, 'activate'])->name('modules.activate');
    Route::post('/modules/{slug}/deactivate', [ModuleController::class, 'deactivate'])->name('modules.deactivate');
});

require __DIR__ . '/auth.php';
