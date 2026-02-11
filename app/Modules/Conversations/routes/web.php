<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Conversations\Http\Controllers\ConversationHubController;
use App\Modules\Conversations\Http\Controllers\ActivityController;

Route::middleware(['web', 'auth'])
    ->prefix('conversations')
    ->name('conversations.')
    ->group(function () {
        Route::get('/', [ConversationHubController::class, 'index'])->name('index');
        Route::post('/start', [ConversationHubController::class, 'start'])->name('start');
        Route::get('/{conversation}', [ConversationHubController::class, 'show'])->name('show');
        Route::get('/{conversation}/logs', [ActivityController::class, 'index'])->name('logs');
        Route::post('/{conversation}/claim', [ConversationHubController::class, 'claim'])->name('claim');
        Route::post('/{conversation}/release', [ConversationHubController::class, 'release'])->name('release');
        Route::post('/{conversation}/invite', [ConversationHubController::class, 'invite'])->name('invite');
        Route::post('/{conversation}/message', [ConversationHubController::class, 'send'])->name('send');
    });
