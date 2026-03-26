<?php

use App\Modules\EmailMarketing\Http\Controllers\EmailCampaignController;
use App\Modules\EmailMarketing\Http\Controllers\EmailAttachmentTemplateController;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:Super-admin|Admin'])
    ->prefix('email-marketing')
    ->name('email-marketing.')
    ->group(function () {
        Route::get('/', [EmailCampaignController::class, 'index'])->name('index');
        Route::get('/create', [EmailCampaignController::class, 'create'])->name('create');
        Route::post('/', [EmailCampaignController::class, 'store'])->name('store');
        Route::post('/matches', [EmailCampaignController::class, 'matchesNew'])->name('matches.new');
        Route::post('/{campaign}/matches', [EmailCampaignController::class, 'matches'])->name('matches');
        // Templates should be registered before catch-all {campaign}
        Route::resource('templates', EmailAttachmentTemplateController::class)->names('templates')->except(['show']);
        Route::get('templates/{emailAttachmentTemplate}/preview', [EmailAttachmentTemplateController::class, 'preview'])->name('templates.preview');
        Route::get('/{campaign}', [EmailCampaignController::class, 'show'])->name('show');
        Route::put('/{campaign}', [EmailCampaignController::class, 'update'])->name('update');
        Route::post('/{campaign}/launch', [EmailCampaignController::class, 'launch'])->name('launch');

        Route::post('/recipients/{recipient}/reply', [EmailCampaignController::class, 'markReply'])->name('recipients.reply');
    });

Route::middleware(['web'])
    ->middleware('throttle:email-marketing-public')
    ->prefix('email-tracking')
    ->name('email-marketing.track.')
    ->group(function () {
        Route::get('/open/{token}', [EmailCampaignController::class, 'trackOpen'])->name('open');
        Route::get('/click/{token}', [EmailCampaignController::class, 'trackClick'])->name('click');
    });

Route::post('/webhook/mailtrap', [\App\Modules\EmailMarketing\Http\Controllers\EmailWebhookController::class, 'mailtrap'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('email-marketing.webhook.mailtrap');

Route::get('/email-unsubscribe/{token}', [\App\Modules\EmailMarketing\Http\Controllers\EmailCampaignController::class, 'unsubscribe'])
    ->middleware(['web', 'throttle:email-marketing-public'])
    ->name('email-marketing.unsubscribe');
