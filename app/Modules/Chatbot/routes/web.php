<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Chatbot\Http\Controllers\ChatbotAccountController;

Route::middleware(['web', 'auth'])
    ->prefix('chatbot')
    ->name('chatbot.')
    ->group(function () {
        Route::resource('accounts', ChatbotAccountController::class)->except(['show']);
    });
