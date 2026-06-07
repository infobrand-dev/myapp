<?php

use App\Modules\Crm\Http\Controllers\Api\CrmLeadApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'auth:sanctum', 'throttle:tenant-api', 'plan.feature:crm'])
    ->prefix('api/crm')
    ->name('crm.api.')
    ->group(function () {
        Route::post('/leads', [CrmLeadApiController::class, 'store'])
            ->middleware('permission:crm.create')
            ->name('leads.store');
    });
