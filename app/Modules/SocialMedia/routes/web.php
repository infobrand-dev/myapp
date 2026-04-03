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
        Route::get('/social-media/webhook/x', [SocialWebhookController::class, 'xCrc'])->name('social-media.webhook.x.crc');
        Route::post('/social-media/webhook/x', [SocialWebhookController::class, 'xInbound'])->name('social-media.webhook.x');
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
        Route::get('/accounts/connect/x', [SocialAccountController::class, 'redirectToX'])->middleware('permission:social_media.manage_accounts')->name('accounts.connect.x');
        Route::get('/accounts/connect/x/callback', [SocialAccountController::class, 'handleXCallback'])->middleware('permission:social_media.manage_accounts')->name('accounts.connect.x.callback');
        Route::post('/accounts/{account}/test-connection', [SocialAccountController::class, 'testConnection'])->middleware('permission:social_media.manage_accounts')->name('accounts.test-connection');
        Route::get('/accounts/internal/x/create', [SocialAccountController::class, 'createXInternal'])->middleware('permission:social_media.manage_accounts')->name('accounts.internal.x.create');
        Route::post('/accounts/internal/x', [SocialAccountController::class, 'storeXInternal'])->middleware('permission:social_media.manage_accounts')->name('accounts.internal.x.store');
        Route::resource('accounts', SocialAccountController::class)->middleware('permission:social_media.manage_accounts')->except(['show', 'create', 'store']);
    });
