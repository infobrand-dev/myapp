<?php

use Illuminate\Support\Facades\Route;
use App\Modules\SocialMedia\Http\Controllers\SocialMediaController;
use App\Modules\SocialMedia\Http\Controllers\SocialWebhookController;
use App\Modules\SocialMedia\Http\Controllers\SocialAccountController;

Route::post('/social-media/webhook', [SocialWebhookController::class, 'inbound'])->name('social-media.webhook');
Route::get('/social-media/webhook', [SocialWebhookController::class, 'verify'])->name('social-media.webhook.verify');

Route::middleware(['web', 'auth'])
    ->prefix('social-media')
    ->name('social-media.')
    ->group(function () {
        Route::get('/', [SocialMediaController::class, 'index'])->name('index');
        Route::resource('accounts', SocialAccountController::class)->except(['show']);
    });
