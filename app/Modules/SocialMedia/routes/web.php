<?php

use Illuminate\Support\Facades\Route;
use App\Modules\SocialMedia\Http\Controllers\SocialMediaController;
use App\Modules\SocialMedia\Http\Controllers\SocialWebhookController;

Route::post('/social-media/webhook', [SocialWebhookController::class, 'inbound'])->name('social-media.webhook');

Route::middleware(['web', 'auth'])
    ->prefix('social-media')
    ->name('social-media.')
    ->group(function () {
        Route::get('/', [SocialMediaController::class, 'index'])->name('index');
    });
