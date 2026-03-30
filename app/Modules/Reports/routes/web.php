<?php

use App\Modules\Reports\Http\Controllers\ReportsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'permission:reports.view', 'plan.feature:advanced_reports'])
    ->prefix('reports')
    ->name('reports.')
    ->group(function () {
        Route::get('/', [ReportsController::class, 'dashboard'])->name('dashboard');
        Route::get('/sales', [ReportsController::class, 'sales'])->middleware('permission:reports.sales')->name('sales');
        Route::get('/payments', [ReportsController::class, 'payments'])->middleware('permission:reports.payments')->name('payments');
        Route::get('/inventory', [ReportsController::class, 'inventory'])->middleware('permission:reports.inventory')->name('inventory');
        Route::get('/purchases', [ReportsController::class, 'purchases'])->middleware('permission:reports.purchases')->name('purchases');
        Route::get('/finance', [ReportsController::class, 'finance'])->middleware('permission:reports.finance')->name('finance');
        Route::get('/pos', [ReportsController::class, 'pos'])->middleware('permission:reports.pos')->name('pos');
    });
