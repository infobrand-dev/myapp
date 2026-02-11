<?php

use App\Modules\WhatsAppBro\Http\Controllers\WhatsAppBroController;
use App\Modules\WhatsAppBro\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/whatsapp-bro/webhook', [WebhookController::class, 'inbound'])->name('whatsapp-bro.webhook');

Route::middleware(['web', 'auth', 'role:Super-admin|Admin'])
    ->prefix('whatsapp-bro')
    ->name('whatsappbro.')
    ->group(function () {
        Route::get('/', [WhatsAppBroController::class, 'index'])->name('index');
    });
