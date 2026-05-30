<?php

use App\Http\Controllers\PlatformBillingMidtransController;
use App\Http\Controllers\PlatformOwnerController;
use App\Http\Middleware\EnsureApexHost;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Platform Public Billing Routes
|--------------------------------------------------------------------------
|
| Public platform billing links and payment webhooks are intentionally kept
| outside the tenant app route file so billing/public access does not mix
| with authenticated workspace navigation.
|
*/

Route::middleware(EnsureApexHost::class)->group(function () {
    Route::post('/platform/billing/midtrans/webhook', [PlatformBillingMidtransController::class, 'notification'])
        ->withoutMiddleware([
            \App\Http\Middleware\VerifyCsrfToken::class,
            \App\Http\Middleware\ResolveTenantFromSubdomain::class,
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        ])
        ->name('platform.billing.midtrans.webhook');

    Route::get('/platform/public/invoices/{invoice}', [PlatformOwnerController::class, 'publicInvoice'])
        ->middleware('signed')
        ->name('platform.invoices.public');

    Route::post('/platform/public/invoices/{invoice}/midtrans/checkout', [PlatformBillingMidtransController::class, 'checkout'])
        ->middleware(['signed'])
        ->withoutMiddleware([
            \App\Http\Middleware\VerifyCsrfToken::class,
        ])
        ->name('platform.invoices.public.midtrans.checkout');
});
