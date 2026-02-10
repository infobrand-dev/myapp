<?php

use Illuminate\Support\Facades\Route;
use App\Modules\WhatsAppApi\Http\Controllers\InstanceController;
use App\Modules\WhatsAppApi\Http\Controllers\ConversationController;
use App\Modules\WhatsAppApi\Http\Controllers\WebhookController;

Route::post('/whatsapp-api/webhook', [WebhookController::class, 'inbound'])->name('whatsapp-api.webhook');

Route::middleware(['web', 'auth'])
    ->prefix('whatsapp-api')
    ->name('whatsapp-api.')
    ->group(function () {
        Route::middleware('role:Super-admin')->group(function () {
            Route::resource('instances', InstanceController::class)->except(['show']);
        });

        Route::get('/', [ConversationController::class, 'index'])->name('inbox');
        Route::post('/conversations/{conversation}/claim', [ConversationController::class, 'claim'])->name('conversations.claim');
        Route::post('/conversations/{conversation}/release', [ConversationController::class, 'release'])->name('conversations.release');
        Route::post('/conversations/{conversation}/invite', [ConversationController::class, 'invite'])->name('conversations.invite');
    });
