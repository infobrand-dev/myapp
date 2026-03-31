<?php

use Illuminate\Support\Facades\Route;
use App\Modules\SocialMedia\Http\Controllers\SocialMediaController;
use App\Modules\SocialMedia\Http\Controllers\SocialWebhookController;
use App\Modules\SocialMedia\Http\Controllers\SocialAccountController;
use App\Http\Middleware\VerifyCsrfToken;

Route::middleware('web')
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->group(function () {
        Route::post('/social-media/webhook', [SocialWebhookController::class, 'inbound'])->name('social-media.webhook');
        Route::get('/social-media/webhook', [SocialWebhookController::class, 'verify'])->name('social-media.webhook.verify');
    });

Route::middleware(['web', 'auth', 'plan.feature:social_media', 'permission:social_media.view'])
    ->prefix('social-media')
    ->name('social-media.')
    ->group(function () {
        Route::get('/', [SocialMediaController::class, 'index'])->name('index');
        Route::get('/conversations/{conversation}', [SocialMediaController::class, 'show'])->name('conversations.show');
        Route::post('/conversations/{conversation}/reply', [SocialMediaController::class, 'reply'])->middleware('permission:social_media.reply')->name('conversations.reply');
        Route::post('/conversations/{conversation}/pause-bot', [SocialMediaController::class, 'pauseBot'])->middleware('permission:social_media.reply')->name('conversations.pause-bot');
        Route::post('/conversations/{conversation}/resume-bot', [SocialMediaController::class, 'resumeBot'])->middleware('permission:social_media.reply')->name('conversations.resume-bot');
        Route::get('/accounts/connect/meta', [SocialAccountController::class, 'redirectToMeta'])->middleware('permission:social_media.manage_accounts')->name('accounts.connect.meta');
        Route::get('/accounts/connect/meta/callback', [SocialAccountController::class, 'handleMetaCallback'])->middleware('permission:social_media.manage_accounts')->name('accounts.connect.meta.callback');
        Route::resource('accounts', SocialAccountController::class)->middleware('permission:social_media.manage_accounts')->except(['show', 'create', 'store']);
    });
