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

Route::middleware(['auth'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->middleware('permission:users.view')->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->middleware('permission:users.create')->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->middleware('permission:users.create')->name('users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->middleware('permission:users.update')->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->middleware('permission:users.update')->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('permission:users.delete')->name('users.destroy');

    Route::get('/roles', [RoleController::class, 'index'])->middleware('permission:roles.view')->name('roles.index');
    Route::get('/roles/create', [RoleController::class, 'create'])->middleware('permission:roles.create')->name('roles.create');
    Route::post('/roles', [RoleController::class, 'store'])->middleware('permission:roles.create')->name('roles.store');
    Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->middleware('permission:roles.update')->name('roles.edit');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->middleware('permission:roles.update')->name('roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:roles.delete')->name('roles.destroy');

    Route::get('/modules', [ModuleController::class, 'index'])->middleware('permission:modules.view')->name('modules.index');
    Route::post('/modules/{slug}/install', [ModuleController::class, 'install'])->middleware('permission:modules.install')->name('modules.install');
    Route::post('/modules/{slug}/activate', [ModuleController::class, 'activate'])->middleware('permission:modules.activate')->name('modules.activate');
    Route::post('/modules/{slug}/deactivate', [ModuleController::class, 'deactivate'])->middleware('permission:modules.deactivate')->name('modules.deactivate');
});

require __DIR__ . '/auth.php';
