<?php

use App\Modules\Reports\Http\Controllers\ReportsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'permission:reports.view', 'plan.feature:accounting'])
    ->prefix('reports')
    ->name('reports.')
    ->group(function () {
        Route::get('/', [ReportsController::class, 'dashboard'])->name('dashboard');
        Route::get('/sales', [ReportsController::class, 'sales'])->middleware(['permission:reports.sales', 'plan.feature:advanced_reports'])->name('sales');
        Route::get('/payments', [ReportsController::class, 'payments'])->middleware(['permission:reports.payments', 'plan.feature:advanced_reports'])->name('payments');
        Route::get('/inventory', [ReportsController::class, 'inventory'])->middleware(['permission:reports.inventory', 'plan.feature:advanced_reports', 'plan.feature:inventory'])->name('inventory');
        Route::get('/purchases', [ReportsController::class, 'purchases'])->middleware(['permission:reports.purchases', 'plan.feature:advanced_reports', 'plan.feature:purchases'])->name('purchases');
        Route::get('/finance', [ReportsController::class, 'finance'])->middleware(['permission:reports.finance', 'plan.feature:advanced_reports'])->name('finance');
        Route::get('/pos', [ReportsController::class, 'pos'])->middleware(['permission:reports.pos', 'plan.feature:advanced_reports', 'plan.feature:point_of_sale'])->name('pos');
    });
