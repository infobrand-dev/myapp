<?php

use App\Http\Controllers\AffiliateProgramController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\TenantOnboardingController;
use Illuminate\Support\Facades\Route;

Route::post('locale/switch', [App\Http\Controllers\LocaleController::class, 'switch'])->name('locale.switch');

Route::post('/webhooks/utas', \App\Http\Controllers\Webhooks\UtasWebhookController::class)
    ->withoutMiddleware([
        \App\Http\Middleware\VerifyCsrfToken::class,
        \App\Http\Middleware\ResolveTenantFromSubdomain::class,
        \App\Http\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    ])
    ->name('webhooks.utas');

Route::get('/', [LandingPageController::class, 'meetra'])->name('landing');
Route::get('/meetra', fn () => redirect()->route('landing'))->name('landing.meetra');
Route::get('/products', [LandingPageController::class, 'products'])->name('products');
Route::get('/contact-us', [LandingPageController::class, 'contact'])->name('contact');
Route::get('/omnichannel', [LandingPageController::class, 'omnichannel'])->name('landing.omnichannel');
Route::get('/accounting', [LandingPageController::class, 'accounting'])->name('landing.accounting');
Route::get('/mulai-digital', [LandingPageController::class, 'mulaiDigital'])->name('landing.mulai-digital');
Route::get('/website-aplikasi-bisnis', [LandingPageController::class, 'websiteApps'])->name('landing.website-apps');
Route::get('/jasa-pembuatan-website', [LandingPageController::class, 'websiteService'])->name('landing.website-service');
Route::get('/affiliate-program', AffiliateProgramController::class)->name('affiliate.program');
Route::get('/aff/{slug}', [LandingPageController::class, 'affiliateRedirect'])->name('affiliate.redirect');
Route::get('/workspace', [LandingPageController::class, 'workspaceFinder'])->name('workspace.finder');
Route::post('/workspace', [LandingPageController::class, 'redirectToWorkspaceLogin'])->name('workspace.redirect');
Route::get('/tentang-kami', [LandingPageController::class, 'about'])->name('about');
Route::get('/keamanan-data', [LandingPageController::class, 'security'])->name('security');
Route::get('/kebijakan-privasi', [LandingPageController::class, 'privacy'])->name('privacy');
Route::get('/syarat-ketentuan', [LandingPageController::class, 'terms'])->name('terms');

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

// SaaS tenant self-registration — only reachable on the apex/root domain (no subdomain)
Route::get('/onboarding', [TenantOnboardingController::class, 'create'])->middleware('throttle:web')->name('onboarding.create');
Route::post('/onboarding', [TenantOnboardingController::class, 'store'])->middleware('throttle:10,5')->name('onboarding.store');
