<?php

use App\Modules\WhatsAppWeb\Http\Controllers\BridgeProcessController;
use App\Modules\WhatsAppWeb\Http\Controllers\WhatsAppWebController;
use App\Modules\WhatsAppWeb\Http\Controllers\ChatController;
use App\Modules\WhatsAppWeb\Http\Controllers\ConversationSyncController;
use App\Modules\WhatsAppWeb\Http\Controllers\SettingsController;
use App\Modules\WhatsAppWeb\Http\Controllers\WebhookController;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::middleware('web')
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->group(function () {
        Route::post('/whatsapp-web/webhook', [WebhookController::class, 'inbound'])->name('whatsappweb.webhook');
        Route::post('/whatsapp-bro/webhook', [WebhookController::class, 'inbound'])->name('whatsapp-bro.webhook');
    });

Route::middleware(['web', 'auth', 'plan.feature:whatsapp_web', 'permission:whatsapp_web.view'])
    ->prefix('whatsapp-web')
    ->name('whatsappweb.')
    ->group(function () {
        Route::get('/', [WhatsAppWebController::class, 'index'])->name('index');
        Route::post('/chats/{chatId}/messages', [ChatController::class, 'send'])->middleware('permission:whatsapp_web.send')->name('chats.messages.send');
        Route::post('/chats/{chatId}/sync', [ConversationSyncController::class, 'syncChat'])->middleware('permission:whatsapp_web.sync')->name('chats.sync');
        Route::post('/sync-active-chats', [ConversationSyncController::class, 'syncActiveChats'])->middleware('permission:whatsapp_web.sync')->name('sync.active');
        Route::middleware('permission:whatsapp_web.manage_settings')->group(function () {
            Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
            Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
            Route::post('/bridge/start', [BridgeProcessController::class, 'start'])->name('bridge.start');
            Route::post('/bridge/stop', [BridgeProcessController::class, 'stop'])->name('bridge.stop');
        });
    });

Route::middleware(['web', 'auth', 'plan.feature:whatsapp_web', 'permission:whatsapp_web.view'])
    ->prefix('whatsapp-bro')
    ->name('whatsappbro.')
    ->group(function () {
        Route::get('/', [WhatsAppWebController::class, 'index'])->name('index');
        Route::middleware('permission:whatsapp_web.manage_settings')->group(function () {
            Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
            Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
        });
    });
