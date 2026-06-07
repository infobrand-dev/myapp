<?php

use App\Http\Controllers\AffiliateProgramController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\TenantOnboardingController;
use App\Http\Middleware\EnsureApexHost;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Apex Public Routes
|--------------------------------------------------------------------------
|
| Marketing, onboarding, and workspace-discovery routes that should only be
| reachable on the apex public host such as meetra.id or the local APP_URL host.
|
*/

Route::middleware(EnsureApexHost::class)->group(function () {
    Route::get('/products', [LandingPageController::class, 'products'])->name('products');
    Route::get('/contact-us', [LandingPageController::class, 'contact'])->name('contact');
    Route::get('/omnichannel', [LandingPageController::class, 'omnichannel'])->name('landing.omnichannel');
    Route::get('/accounting', [LandingPageController::class, 'accounting'])->name('landing.accounting');
    Route::get('/commerce', [LandingPageController::class, 'commerce'])->name('landing.commerce');
    Route::get('/crm', [LandingPageController::class, 'crm'])->name('landing.crm');
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

    Route::get('/onboarding', [TenantOnboardingController::class, 'create'])
        ->middleware('throttle:web')
        ->name('onboarding.create');
    Route::post('/onboarding', [TenantOnboardingController::class, 'store'])
        ->middleware('throttle:10,5')
        ->name('onboarding.store');
});
