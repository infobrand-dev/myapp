<?php

use App\Modules\Affiliate\Http\Controllers\AffiliateController;
use App\Modules\Affiliate\Http\Controllers\PublicAffiliateListingController;
use App\Modules\Affiliate\Http\Controllers\AffiliateReferralCaptureController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'plan.feature:commerce', 'plan.feature:storefront'])
    ->name('affiliate.public.')
    ->group(function () {
        Route::get('/ref/{code}', AffiliateReferralCaptureController::class)->name('capture');
        Route::get('/affiliate/products/{listing:share_code}', PublicAffiliateListingController::class)->name('products.show');
    });

Route::middleware(['web', 'auth', 'plan.feature:commerce', 'permission:affiliate.view'])
    ->prefix('affiliate')
    ->name('affiliate.')
    ->group(function () {
        Route::get('/', [AffiliateController::class, 'index'])->name('index');
        Route::get('/marketplace', [AffiliateController::class, 'marketplace'])->name('marketplace');
        Route::post('/marketplace/{sourceProduct}/claim', [AffiliateController::class, 'claim'])
            ->middleware('permission:affiliate.manage')
            ->name('marketplace.claim');
        Route::post('/listings/{listing}', [AffiliateController::class, 'updateListing'])
            ->middleware('permission:affiliate.manage')
            ->name('listings.update');
    });
