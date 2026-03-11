<?php

use App\Modules\WhatsAppBro\Http\Controllers\WhatsAppBroController;
use App\Modules\WhatsAppBro\Http\Controllers\SettingsController;
use App\Modules\WhatsAppBro\Http\Controllers\WebhookController;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::middleware('web')
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->group(function () {
        Route::post('/whatsapp-bro/webhook', [WebhookController::class, 'inbound'])->name('whatsapp-bro.webhook');
    });

Route::middleware(['web', 'auth', 'role:Super-admin|Admin'])
    ->prefix('whatsapp-bro')
    ->name('whatsappbro.')
    ->group(function () {
        Route::get('/', [WhatsAppBroController::class, 'index'])->name('index');
        Route::middleware('role:Super-admin')->group(function () {
            Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
            Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
        });
    });
