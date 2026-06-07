<?php

use App\Modules\Crm\Http\Controllers\CrmLeadCaptureWebhookController;
use App\Modules\Crm\Http\Controllers\CrmLeadController;
use App\Modules\Crm\Http\Controllers\CrmWorkspaceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'throttle:public-webhook', 'plan.feature:crm'])
    ->prefix('crm/webhooks')
    ->name('crm.webhooks.')
    ->group(function () {
        Route::post('/lead-capture', [CrmLeadCaptureWebhookController::class, 'store'])->name('lead-capture');
        Route::post('/meta-leads', [CrmLeadCaptureWebhookController::class, 'metaLeadAds'])->name('meta-leads');
    });

Route::middleware(['web', 'auth', 'plan.feature:crm', 'permission:crm.view'])
    ->prefix('crm')
    ->name('crm.')
    ->group(function () {
        Route::get('/', [CrmWorkspaceController::class, 'dashboard'])->name('dashboard');
        Route::get('/leads', [CrmLeadController::class, 'index'])->name('index');
        Route::get('/leads/export', [CrmLeadController::class, 'export'])->middleware('permission:crm.export')->name('export');
        Route::get('/leads/create', [CrmLeadController::class, 'create'])->middleware('permission:crm.create')->name('create');
        Route::post('/leads', [CrmLeadController::class, 'store'])->middleware('permission:crm.create')->name('store');
        Route::get('/leads/{lead}', [CrmLeadController::class, 'show'])->name('show');
        Route::get('/leads/{lead}/edit', [CrmLeadController::class, 'edit'])->middleware('permission:crm.update')->name('edit');
        Route::put('/leads/{lead}', [CrmLeadController::class, 'update'])->middleware('permission:crm.update')->name('update');
        Route::post('/leads/{lead}/stage', [CrmLeadController::class, 'updateStage'])->middleware('permission:crm.update')->name('stage');
        Route::delete('/leads/{lead}', [CrmLeadController::class, 'destroy'])->middleware('permission:crm.delete')->name('destroy');

        Route::get('/follow-up', [CrmWorkspaceController::class, 'followUps'])->name('follow-ups');
        Route::post('/follow-up', [CrmWorkspaceController::class, 'storeFollowUp'])->middleware('permission:crm.create')->name('follow-ups.store');
        Route::post('/follow-up/{task}/complete', [CrmWorkspaceController::class, 'completeFollowUp'])->middleware('permission:crm.update')->name('follow-ups.complete');

        Route::get('/customers', [CrmWorkspaceController::class, 'customers'])->name('customers');
        Route::get('/customers/{contact}', [CrmWorkspaceController::class, 'customerShow'])->name('customers.show');

        Route::get('/pipelines', [CrmWorkspaceController::class, 'pipelines'])->name('pipelines');
        Route::post('/pipelines', [CrmWorkspaceController::class, 'storePipeline'])->middleware('permission:crm.manage_pipeline')->name('pipelines.store');
        Route::post('/pipelines/{pipeline}/stages', [CrmWorkspaceController::class, 'storePipelineStage'])->middleware('permission:crm.manage_pipeline')->name('pipelines.stages.store');
        Route::post('/pipelines/{pipeline}/reorder', [CrmWorkspaceController::class, 'reorderPipelineStages'])->middleware('permission:crm.manage_pipeline')->name('pipelines.reorder');

        Route::get('/settings', [CrmWorkspaceController::class, 'settings'])->name('settings');
        Route::post('/settings', [CrmWorkspaceController::class, 'updateSettings'])->middleware('permission:crm.manage_pipeline')->name('settings.update');
        Route::post('/settings/webhook-receipts/{receipt}/replay', [CrmWorkspaceController::class, 'replayWebhookReceipt'])->middleware('permission:crm.manage_pipeline')->name('settings.webhook-replay');
        Route::get('/onboarding', [CrmWorkspaceController::class, 'onboarding'])->name('onboarding');
        Route::post('/onboarding/complete', [CrmWorkspaceController::class, 'completeOnboarding'])->name('onboarding.complete');
    });
