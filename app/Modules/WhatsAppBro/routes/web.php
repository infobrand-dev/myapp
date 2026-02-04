<?php

use App\Modules\WhatsAppBro\Http\Controllers\WhatsAppBroController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:Super-admin|Admin'])
    ->prefix('whatsapp-bro')
    ->name('whatsappbro.')
    ->group(function () {
        Route::get('/', [WhatsAppBroController::class, 'index'])->name('index');
    });
