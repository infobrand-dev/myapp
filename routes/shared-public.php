<?php

use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PublicRootController;
use App\Http\Controllers\StoredFileController;
use App\Http\Controllers\Auth\TenantDomainHandoffController;
use App\Http\Controllers\Webhooks\UtasWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Shared Public Entry Routes
|--------------------------------------------------------------------------
|
| Routes in this file are intentionally shared because they are safe to
| resolve before deciding whether the current host is the apex public site
| or a tenant/platform subdomain.
|
*/

Route::post('locale/switch', [LocaleController::class, 'switch'])->name('locale.switch');

Route::post('/webhooks/utas', UtasWebhookController::class)
    ->middleware('throttle:public-webhook')
    ->withoutMiddleware([
        \App\Http\Middleware\VerifyCsrfToken::class,
        \App\Http\Middleware\ResolveTenantFromSubdomain::class,
        \App\Http\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    ])
    ->name('webhooks.utas');

Route::get('/', PublicRootController::class)->name('landing');
Route::get('/meetra', fn () => redirect()->route('landing'))->name('landing.meetra');
Route::get('/files/shared/{storedFileId}', [StoredFileController::class, 'share'])
    ->middleware(['signed', 'throttle:sensitive'])
    ->name('stored-files.share');
Route::get('/domain-handoff/{token}', TenantDomainHandoffController::class)
    ->middleware(['signed', 'throttle:sensitive'])
    ->name('tenant.domain-handoff.consume');

// Health check — no auth, no session, no CSRF. Used by uptime monitors and load balancers.
Route::get('/health', function () {
    $checks = [];

    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        $checks['database'] = 'ok';
    } catch (\Throwable $e) {
        $checks['database'] = 'error';
    }

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
    ], $healthy ? 200 : 503);
})->withoutMiddleware([
    \App\Http\Middleware\EnsureInstalled::class,
    \App\Http\Middleware\ResolveTenantFromSubdomain::class,
    \App\Http\Middleware\VerifyCsrfToken::class,
    \App\Http\Middleware\EncryptCookies::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
])->name('health');
