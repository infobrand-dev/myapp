<?php

use App\Modules\Storefront\Http\Controllers\StorefrontController;
use App\Modules\Storefront\Http\Controllers\StorefrontCartController;
use App\Modules\Storefront\Http\Controllers\PublicStorefrontController;
use App\Modules\Storefront\Http\Controllers\StorefrontCheckoutController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'throttle:commerce-checkout', 'plan.feature:commerce', 'plan.feature:storefront'])
    ->prefix('shop')
    ->name('storefront.public.')
    ->group(function () {
        Route::get('/', [PublicStorefrontController::class, 'index'])->name('index');
        Route::get('/cart', [PublicStorefrontController::class, 'cart'])->name('cart');
        Route::get('/checkout', [PublicStorefrontController::class, 'checkout'])->name('checkout');
        Route::get('/products/{product:slug}', [PublicStorefrontController::class, 'show'])->name('products.show');
        Route::post('/products/{product:slug}/cart', [StorefrontCartController::class, 'add'])->name('cart.add');
        Route::post('/products/{product:slug}/buy-now', [StorefrontCartController::class, 'buyNow'])->name('buy-now');
        Route::post('/cart/items/{product:slug}', [StorefrontCartController::class, 'update'])->name('cart.update');
        Route::post('/cart/items/{product:slug}/remove', [StorefrontCartController::class, 'remove'])->name('cart.remove');
        Route::post('/cart/clear', [StorefrontCartController::class, 'clear'])->name('cart.clear');
        Route::post('/checkout', [StorefrontCheckoutController::class, 'checkout'])->name('checkout.cart');
        Route::post('/products/{product:slug}/checkout', [StorefrontCheckoutController::class, 'store'])->name('checkout.store');
        Route::get('/orders/{sale}', [PublicStorefrontController::class, 'order'])
            ->middleware('signed')
            ->name('orders.show');
        Route::post('/orders/{sale}/retry-payment', [StorefrontCheckoutController::class, 'retryPayment'])
            ->middleware('signed')
            ->name('orders.retry-payment');
    });

Route::middleware(['web', 'auth', 'plan.feature:commerce', 'plan.feature:storefront', 'permission:storefront.view'])
    ->prefix('storefront')
    ->name('storefront.')
    ->group(function () {
        Route::get('/', StorefrontController::class)->name('index');
    });
