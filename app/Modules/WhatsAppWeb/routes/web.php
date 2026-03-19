<?php

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

Route::middleware(['web', 'auth', 'role:Super-admin|Admin'])
    ->prefix('whatsapp-web')
    ->name('whatsappweb.')
    ->group(function () {
        Route::get('/', [WhatsAppWebController::class, 'index'])->name('index');
        Route::post('/chats/{chatId}/messages', [ChatController::class, 'send'])->name('chats.messages.send');
        Route::post('/chats/{chatId}/sync', [ConversationSyncController::class, 'syncChat'])->name('chats.sync');
        Route::post('/sync-active-chats', [ConversationSyncController::class, 'syncActiveChats'])->name('sync.active');
        Route::middleware('role:Super-admin')->group(function () {
            Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
            Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
        });
    });

Route::middleware(['web', 'auth', 'role:Super-admin|Admin'])
    ->prefix('whatsapp-bro')
    ->name('whatsappbro.')
    ->group(function () {
        Route::get('/', [WhatsAppWebController::class, 'index'])->name('index');
        Route::middleware('role:Super-admin')->group(function () {
            Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
            Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
        });
    });
