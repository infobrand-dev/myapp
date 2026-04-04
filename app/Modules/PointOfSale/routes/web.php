<?php

use App\Modules\PointOfSale\Http\Controllers\BarcodeScanController;
use App\Modules\PointOfSale\Http\Controllers\CashSessionController;
use App\Modules\PointOfSale\Http\Controllers\CheckoutController;
use App\Modules\PointOfSale\Http\Controllers\HeldCartController;
use App\Modules\PointOfSale\Http\Controllers\PosDiscountController;
use App\Modules\PointOfSale\Http\Controllers\PosCartController;
use App\Modules\PointOfSale\Http\Controllers\PosCartItemController;
use App\Modules\PointOfSale\Http\Controllers\PosScreenController;
use App\Modules\PointOfSale\Http\Controllers\PosWorkspaceController;
use App\Modules\PointOfSale\Http\Controllers\ReceiptController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'plan.feature:commerce', 'plan.feature:point_of_sale'])
    ->prefix('pos')
    ->name('pos.')
    ->group(function () {
        Route::get('/', [PosScreenController::class, 'index'])->middleware('permission:pos.use')->name('index');
        Route::get('/shifts', [CashSessionController::class, 'index'])->middleware('permission:pos.view-shift')->name('shifts.index');
        Route::get('/shifts/open', [CashSessionController::class, 'create'])->middleware('permission:pos.open-shift')->name('shifts.create');
        Route::post('/shifts', [CashSessionController::class, 'store'])->middleware('permission:pos.open-shift')->name('shifts.store');
        Route::get('/shifts/{shift}', [CashSessionController::class, 'show'])->middleware('permission:pos.view-shift')->name('shifts.show');
        Route::post('/shifts/{shift}/close', [CashSessionController::class, 'close'])->middleware('permission:pos.close-shift')->name('shifts.close');
        Route::post('/shifts/{shift}/movements', [CashSessionController::class, 'storeMovement'])->middleware('permission:pos.record-cash-movement')->name('shifts.movements.store');
        Route::get('/workspace', [PosWorkspaceController::class, 'show'])->middleware('permission:pos.use')->name('workspace');
        Route::get('/products/search', [PosWorkspaceController::class, 'searchProducts'])->middleware('permission:pos.use')->name('products.search');
        Route::get('/customers/search', [PosWorkspaceController::class, 'searchCustomers'])->middleware('permission:pos.use')->name('customers.search');
        Route::get('/cart/active', [PosCartController::class, 'active'])->middleware('permission:pos.use')->name('cart.active');
        Route::patch('/cart/active', [PosCartController::class, 'update'])->middleware('permission:pos.use')->name('cart.update');
        Route::delete('/cart/active', [PosCartController::class, 'clear'])->middleware('permission:pos.use')->name('cart.clear');
        Route::post('/cart/items', [PosCartItemController::class, 'store'])->middleware('permission:pos.use')->name('cart.items.store');
        Route::patch('/cart/items/{item}', [PosCartItemController::class, 'update'])->middleware('permission:pos.use')->name('cart.items.update');
        Route::delete('/cart/items/{item}', [PosCartItemController::class, 'destroy'])->middleware('permission:pos.use')->name('cart.items.destroy');
        Route::post('/barcode/scan', [BarcodeScanController::class, 'store'])->middleware('permission:pos.use')->name('barcode.scan');
        Route::get('/held-carts', [HeldCartController::class, 'index'])->middleware('permission:pos.resume-cart')->name('held.index');
        Route::post('/held-carts', [HeldCartController::class, 'store'])->middleware('permission:pos.hold-cart')->name('held.store');
        Route::post('/held-carts/{cart}/resume', [HeldCartController::class, 'resume'])->middleware('permission:pos.resume-cart')->name('held.resume');
        Route::post('/discounts/evaluate', [PosDiscountController::class, 'evaluate'])->middleware('permission:pos.use')->name('discounts.evaluate');
        Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('permission:pos.checkout')->name('checkout.store');
        Route::get('/receipts/{sale}', [ReceiptController::class, 'show'])->middleware('permission:pos.print-receipt')->name('receipts.show');
        Route::get('/receipts/{sale}/print', [ReceiptController::class, 'print'])->middleware('permission:pos.print-receipt')->name('receipts.print');
        Route::post('/receipts/{sale}/reprint', [ReceiptController::class, 'reprint'])->middleware('permission:pos.reprint-receipt')->name('receipts.reprint');
    });
