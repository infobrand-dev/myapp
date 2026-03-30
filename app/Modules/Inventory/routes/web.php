<?php

use App\Modules\Inventory\Http\Controllers\InventoryDashboardController;
use App\Modules\Inventory\Http\Controllers\LowStockReportController;
use App\Modules\Inventory\Http\Controllers\OpeningStockController;
use App\Modules\Inventory\Http\Controllers\StockAdjustmentController;
use App\Modules\Inventory\Http\Controllers\StockController;
use App\Modules\Inventory\Http\Controllers\StockMovementController;
use App\Modules\Inventory\Http\Controllers\StockOpnameController;
use App\Modules\Inventory\Http\Controllers\StockTransferController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'plan.feature:commerce'])
    ->prefix('inventory')
    ->name('inventory.')
    ->group(function () {
        Route::get('/', InventoryDashboardController::class)->middleware('permission:inventory.view-stock')->name('dashboard');

        Route::get('/stocks', [StockController::class, 'index'])->middleware('permission:inventory.view-stock')->name('stocks.index');
        Route::get('/stocks/{stock}', [StockController::class, 'show'])->middleware('permission:inventory.view-stock')->name('stocks.show');

        Route::get('/movements', [StockMovementController::class, 'index'])->middleware('permission:inventory.view-movement')->name('movements.index');

        Route::get('/openings', [OpeningStockController::class, 'index'])->middleware('permission:inventory.manage-opening-stock')->name('openings.index');
        Route::get('/openings/create', [OpeningStockController::class, 'create'])->middleware('permission:inventory.manage-opening-stock')->name('openings.create');
        Route::post('/openings', [OpeningStockController::class, 'store'])->middleware('permission:inventory.manage-opening-stock')->name('openings.store');

        Route::get('/adjustments', [StockAdjustmentController::class, 'index'])->middleware('permission:inventory.manage-stock-adjustment')->name('adjustments.index');
        Route::get('/adjustments/create', [StockAdjustmentController::class, 'create'])->middleware('permission:inventory.manage-stock-adjustment')->name('adjustments.create');
        Route::post('/adjustments', [StockAdjustmentController::class, 'store'])->middleware('permission:inventory.manage-stock-adjustment')->name('adjustments.store');
        Route::get('/adjustments/{adjustment}', [StockAdjustmentController::class, 'show'])->middleware('permission:inventory.manage-stock-adjustment')->name('adjustments.show');
        Route::post('/adjustments/{adjustment}/finalize', [StockAdjustmentController::class, 'finalize'])->middleware('permission:inventory.finalize-stock-adjustment')->name('adjustments.finalize');

        Route::get('/opnames', [StockOpnameController::class, 'index'])->middleware('permission:inventory.manage-stock-opname')->name('opnames.index');
        Route::get('/opnames/create', [StockOpnameController::class, 'create'])->middleware('permission:inventory.manage-stock-opname')->name('opnames.create');
        Route::post('/opnames', [StockOpnameController::class, 'store'])->middleware('permission:inventory.manage-stock-opname')->name('opnames.store');
        Route::get('/opnames/{opname}', [StockOpnameController::class, 'show'])->middleware('permission:inventory.manage-stock-opname')->name('opnames.show');
        Route::put('/opnames/{opname}', [StockOpnameController::class, 'update'])->middleware('permission:inventory.manage-stock-opname')->name('opnames.update');
        Route::post('/opnames/{opname}/finalize', [StockOpnameController::class, 'finalize'])->middleware('permission:inventory.finalize-stock-opname')->name('opnames.finalize');

        Route::get('/transfers', [StockTransferController::class, 'index'])->middleware('permission:inventory.manage-stock-transfer')->name('transfers.index');
        Route::get('/transfers/create', [StockTransferController::class, 'create'])->middleware('permission:inventory.manage-stock-transfer')->name('transfers.create');
        Route::post('/transfers', [StockTransferController::class, 'store'])->middleware('permission:inventory.manage-stock-transfer')->name('transfers.store');
        Route::get('/transfers/{transfer}', [StockTransferController::class, 'show'])->middleware('permission:inventory.manage-stock-transfer')->name('transfers.show');
        Route::post('/transfers/{transfer}/approve', [StockTransferController::class, 'approve'])->middleware('permission:inventory.approve-stock-transfer')->name('transfers.approve');
        Route::post('/transfers/{transfer}/send', [StockTransferController::class, 'send'])->middleware('permission:inventory.manage-stock-transfer')->name('transfers.send');
        Route::post('/transfers/{transfer}/receive', [StockTransferController::class, 'receive'])->middleware('permission:inventory.manage-stock-transfer')->name('transfers.receive');

        Route::get('/reports/low-stock', LowStockReportController::class)->middleware('permission:inventory.view-stock')->name('reports.low-stock');
    });
