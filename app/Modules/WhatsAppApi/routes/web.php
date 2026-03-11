<?php

use Illuminate\Support\Facades\Route;
use App\Modules\WhatsAppApi\Http\Controllers\InstanceController;
use App\Modules\WhatsAppApi\Http\Controllers\ConversationController;
use App\Modules\WhatsAppApi\Http\Controllers\ContactActionController;
use App\Modules\WhatsAppApi\Http\Controllers\MessageLogController;
use App\Modules\WhatsAppApi\Http\Controllers\WebhookController;
use App\Modules\WhatsAppApi\Http\Controllers\WATemplateController;
use App\Modules\WhatsAppApi\Http\Controllers\BlastCampaignController;
use App\Http\Middleware\VerifyCsrfToken;

Route::middleware('web')
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->group(function () {
        Route::post('/whatsapp-api/webhook', [WebhookController::class, 'inbound'])->name('whatsapp-api.webhook');
        Route::get('/whatsapp-api/webhook', [WebhookController::class, 'verify'])->name('whatsapp-api.webhook.verify');
    });

Route::middleware(['web', 'auth'])
    ->prefix('whatsapp-api')
    ->name('whatsapp-api.')
    ->group(function () {
        Route::middleware('role:Super-admin')->group(function () {
            Route::post('instances/test-credentials', [InstanceController::class, 'testCredentials'])->name('instances.test-credentials');
            Route::post('instances/sync-templates', [InstanceController::class, 'syncTemplates'])->name('instances.sync-templates');
            Route::match(['post', 'put', 'patch'], 'instances/{instance}/save-and-test', [InstanceController::class, 'saveAndTest'])->name('instances.save-and-test');
            Route::match(['post', 'put', 'patch'], 'instances/{instance}/save-and-sync-templates', [InstanceController::class, 'saveAndSyncTemplates'])->name('instances.save-and-sync-templates');
            Route::resource('instances', InstanceController::class)->except(['show']);
            Route::resource('templates', WATemplateController::class)->except(['show']);
            Route::post('templates/{template}/submit', [WATemplateController::class, 'submit'])->name('templates.submit');
            Route::get('blast-campaigns', [BlastCampaignController::class, 'index'])->name('blast-campaigns.index');
            Route::get('blast-campaigns/create', [BlastCampaignController::class, 'create'])->name('blast-campaigns.create');
            Route::post('blast-campaigns/matches', [BlastCampaignController::class, 'matches'])->name('blast-campaigns.matches');
            Route::post('blast-campaigns', [BlastCampaignController::class, 'store'])->name('blast-campaigns.store');
            Route::post('blast-campaigns/{blastCampaign}/launch', [BlastCampaignController::class, 'launch'])->name('blast-campaigns.launch');
            Route::post('blast-campaigns/{blastCampaign}/retry-failed', [BlastCampaignController::class, 'retryFailed'])->name('blast-campaigns.retry-failed');
            Route::delete('blast-campaigns/{blastCampaign}', [BlastCampaignController::class, 'destroy'])->name('blast-campaigns.destroy');
            Route::get('logs', [MessageLogController::class, 'index'])->name('logs.index');
            Route::post('logs/retry-failed', [MessageLogController::class, 'retryFailed'])->name('logs.retry-failed');
            Route::post('logs/{message}/requeue', [MessageLogController::class, 'requeue'])->name('logs.requeue');
        });

        Route::get('/', [ConversationController::class, 'index'])->name('inbox');
        Route::post('/contact-actions/send-template', [ContactActionController::class, 'sendTemplate'])->name('contact-actions.send-template');
        Route::post('/conversations/{conversation}/claim', [ConversationController::class, 'claim'])->name('conversations.claim');
        Route::post('/conversations/{conversation}/release', [ConversationController::class, 'release'])->name('conversations.release');
        Route::post('/conversations/{conversation}/invite', [ConversationController::class, 'invite'])->name('conversations.invite');
    });
