<?php

use App\Http\Controllers\RoleController;
use App\Http\Controllers\TenantOnboardingController;
use App\Http\Controllers\SettingsController;
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

// Health check — no auth, no session, no CSRF. Used by uptime monitors and load balancers.
Route::get('/health', function () {
    $checks = [];

    // Database
    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        $checks['database'] = 'ok';
    } catch (\Throwable $e) {
        $checks['database'] = 'error';
    }

    // Cache
    try {
        \Illuminate\Support\Facades\Cache::put('_health', 1, 5);
        $checks['cache'] = 'ok';
    } catch (\Throwable $e) {
        $checks['cache'] = 'error';
    }

    $healthy = ! in_array('error', $checks, true);

    return response()->json([
        'status'  => $healthy ? 'ok' : 'degraded',
        'checks'  => $checks,
        'version' => config('app.version', '1.0.0'),
        'env'     => app()->environment(),
    ], $healthy ? 200 : 503);
})->withoutMiddleware([
    \App\Http\Middleware\EnsureInstalled::class,
    \App\Http\Middleware\ResolveTenantContext::class,
    \App\Http\Middleware\ResolveCompanyContext::class,
    \App\Http\Middleware\ResolveBranchContext::class,
    \App\Http\Middleware\VerifyCsrfToken::class,
    \App\Http\Middleware\EncryptCookies::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
])->name('health');

// SaaS tenant self-registration — only reachable on the apex/root domain (no subdomain)
Route::get('/onboarding', [TenantOnboardingController::class, 'create'])->middleware('throttle:web')->name('onboarding.create');
Route::post('/onboarding', [TenantOnboardingController::class, 'store'])->middleware('throttle:10,5')->name('onboarding.store');

Route::withoutMiddleware([
    \App\Http\Middleware\EncryptCookies::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \App\Http\Middleware\VerifyCsrfToken::class,
])->group(function () {
    Route::get('/install', [InstallController::class, 'index'])->name('install.index');
    Route::post('/install/test-db', [InstallController::class, 'testDatabase'])->middleware('throttle:10,1')->name('install.test-db');
    Route::post('/install/run', [InstallController::class, 'run'])->middleware('throttle:5,1')->name('install.run');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');

    Route::middleware('permission:settings.view')->group(function () {
        Route::get('/settings', [SettingsController::class, 'show'])->defaults('section', 'general')->name('settings.general');
        Route::get('/settings/company', [SettingsController::class, 'show'])->defaults('section', 'company')->name('settings.company');
        Route::get('/settings/branch', [SettingsController::class, 'show'])->defaults('section', 'branch')->name('settings.branch');
        Route::get('/settings/documents', [SettingsController::class, 'show'])->defaults('section', 'documents')->name('settings.documents');
        Route::get('/settings/subscription', [SettingsController::class, 'show'])->defaults('section', 'subscription')->name('settings.subscription');
        Route::get('/settings/access', [SettingsController::class, 'show'])->defaults('section', 'access')->name('settings.access');
        Route::get('/settings/modules', [SettingsController::class, 'show'])->defaults('section', 'modules')->name('settings.modules');

        Route::post('/settings/company/switch/{company}', [SettingsController::class, 'switchCompany'])->name('settings.company.switch');
        Route::post('/settings/branch/switch/{branch}', [SettingsController::class, 'switchBranch'])->name('settings.branch.switch');
        Route::post('/settings/branch/clear', [SettingsController::class, 'clearBranch'])->name('settings.branch.clear');
    });

    Route::middleware('permission:settings.manage')->group(function () {
        Route::post('/settings/company', [SettingsController::class, 'storeCompany'])->name('settings.company.store');
        Route::put('/settings/company/{company}', [SettingsController::class, 'updateCompany'])->name('settings.company.update');
        Route::post('/settings/company/{company}/activate', [SettingsController::class, 'activateCompany'])->name('settings.company.activate');

        Route::post('/settings/branch', [SettingsController::class, 'storeBranch'])->name('settings.branch.store');
        Route::put('/settings/branch/{branch}', [SettingsController::class, 'updateBranch'])->name('settings.branch.update');
        Route::post('/settings/branch/{branch}/activate', [SettingsController::class, 'activateBranch'])->name('settings.branch.activate');

        Route::put('/settings/documents', [SettingsController::class, 'saveDocuments'])->name('settings.documents.save');
    });
});

Route::middleware(['auth', 'verified'])->prefix('presence')->name('presence.')->group(function () {
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
