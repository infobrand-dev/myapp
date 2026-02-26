<?php

use Illuminate\Support\Facades\Route;
use App\Modules\WhatsAppApi\Http\Controllers\InstanceController;
use App\Modules\WhatsAppApi\Http\Controllers\ConversationController;
use App\Modules\WhatsAppApi\Http\Controllers\MessageLogController;
use App\Modules\WhatsAppApi\Http\Controllers\WebhookController;
use App\Modules\WhatsAppApi\Http\Controllers\WATemplateController;

Route::post('/whatsapp-api/webhook', [WebhookController::class, 'inbound'])->name('whatsapp-api.webhook');
Route::get('/whatsapp-api/webhook', [WebhookController::class, 'verify'])->name('whatsapp-api.webhook.verify');

Route::middleware(['web', 'auth'])
    ->prefix('whatsapp-api')
    ->name('whatsapp-api.')
    ->group(function () {
        Route::middleware('role:Super-admin')->group(function () {
            Route::post('instances/test-credentials', [InstanceController::class, 'testCredentials'])->name('instances.test-credentials');
            Route::post('instances/sync-templates', [InstanceController::class, 'syncTemplates'])->name('instances.sync-templates');
            Route::resource('instances', InstanceController::class)->except(['show']);
            Route::resource('templates', WATemplateController::class)->except(['show']);
            Route::post('templates/{template}/submit', [WATemplateController::class, 'submit'])->name('templates.submit');
            Route::get('logs', [MessageLogController::class, 'index'])->name('logs.index');
            Route::post('logs/retry-failed', [MessageLogController::class, 'retryFailed'])->name('logs.retry-failed');
            Route::post('logs/{message}/requeue', [MessageLogController::class, 'requeue'])->name('logs.requeue');
        });

        Route::get('/', [ConversationController::class, 'index'])->name('inbox');
        Route::post('/conversations/{conversation}/claim', [ConversationController::class, 'claim'])->name('conversations.claim');
        Route::post('/conversations/{conversation}/release', [ConversationController::class, 'release'])->name('conversations.release');
        Route::post('/conversations/{conversation}/invite', [ConversationController::class, 'invite'])->name('conversations.invite');
    });
