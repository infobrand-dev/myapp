<?php

use App\Modules\Crm\Http\Controllers\CrmLeadController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'plan.feature:crm', 'permission:crm.view'])
    ->prefix('crm')
    ->name('crm.')
    ->group(function () {
        Route::get('/', [CrmLeadController::class, 'index'])->name('index');
        Route::get('/create', [CrmLeadController::class, 'create'])->middleware('permission:crm.create')->name('create');
        Route::post('/', [CrmLeadController::class, 'store'])->middleware('permission:crm.create')->name('store');
        Route::get('/{lead}', [CrmLeadController::class, 'show'])->name('show');
        Route::get('/{lead}/edit', [CrmLeadController::class, 'edit'])->middleware('permission:crm.update')->name('edit');
        Route::put('/{lead}', [CrmLeadController::class, 'update'])->middleware('permission:crm.update')->name('update');
        Route::post('/{lead}/stage', [CrmLeadController::class, 'updateStage'])->middleware('permission:crm.update')->name('stage');
        Route::delete('/{lead}', [CrmLeadController::class, 'destroy'])->middleware('permission:crm.delete')->name('destroy');
    });
