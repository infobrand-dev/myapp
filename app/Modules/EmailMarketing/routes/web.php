<?php

use App\Modules\EmailMarketing\Http\Controllers\EmailCampaignController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:Super-admin|Admin'])
    ->prefix('email-marketing')
    ->name('email-marketing.')
    ->group(function () {
        Route::get('/', [EmailCampaignController::class, 'index'])->name('index');
        Route::get('/create', [EmailCampaignController::class, 'create'])->name('create');
        Route::post('/', [EmailCampaignController::class, 'store'])->name('store');
        Route::post('/{campaign}/matches', [EmailCampaignController::class, 'matches'])->name('matches');
        Route::get('/{campaign}', [EmailCampaignController::class, 'show'])->name('show');
        Route::put('/{campaign}', [EmailCampaignController::class, 'update'])->name('update');
        Route::post('/{campaign}/launch', [EmailCampaignController::class, 'launch'])->name('launch');

        Route::post('/recipients/{recipient}/reply', [EmailCampaignController::class, 'markReply'])->name('recipients.reply');
    });

Route::middleware(['web'])
    ->prefix('email-tracking')
    ->name('email-marketing.track.')
    ->group(function () {
        Route::get('/open/{token}', [EmailCampaignController::class, 'trackOpen'])->name('open');
        Route::get('/click/{token}', [EmailCampaignController::class, 'trackClick'])->name('click');
    });
