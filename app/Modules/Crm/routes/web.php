<?php

use App\Modules\Crm\Http\Controllers\CrmLeadController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:Super-admin|Admin', 'plan.feature:crm'])
    ->prefix('crm')
    ->name('crm.')
    ->group(function () {
        Route::get('/', [CrmLeadController::class, 'index'])->name('index');
        Route::get('/create', [CrmLeadController::class, 'create'])->name('create');
        Route::post('/', [CrmLeadController::class, 'store'])->name('store');
        Route::get('/{lead}', [CrmLeadController::class, 'show'])->name('show');
        Route::get('/{lead}/edit', [CrmLeadController::class, 'edit'])->name('edit');
        Route::put('/{lead}', [CrmLeadController::class, 'update'])->name('update');
        Route::post('/{lead}/stage', [CrmLeadController::class, 'updateStage'])->name('stage');
        Route::delete('/{lead}', [CrmLeadController::class, 'destroy'])->name('destroy');
    });
