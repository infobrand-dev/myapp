<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Conversations\Http\Controllers\ConversationHubController;
use App\Modules\Conversations\Http\Controllers\ActivityController;

Route::middleware(['web', 'auth'])
    ->prefix('conversations')
    ->name('conversations.')
    ->group(function () {
        Route::get('/', [ConversationHubController::class, 'index'])->name('index');
        Route::get('/users/search', [ConversationHubController::class, 'searchUsers'])->name('users.search');
        Route::post('/start', [ConversationHubController::class, 'start'])->name('start');
        Route::get('/{conversation}', [ConversationHubController::class, 'show'])->name('show');
        Route::get('/{conversation}/messages', [ConversationHubController::class, 'messages'])->name('messages');
        Route::get('/{conversation}/messages/since', [ConversationHubController::class, 'messagesSince'])->name('messages.since');
        Route::post('/{conversation}/read', [ConversationHubController::class, 'read'])
            ->middleware('throttle:60,1')
            ->name('read');
        Route::get('/{conversation}/logs', [ActivityController::class, 'index'])->name('logs');
        Route::post('/{conversation}/claim', [ConversationHubController::class, 'claim'])
            ->middleware('throttle:30,1')
            ->name('claim');
        Route::post('/{conversation}/release', [ConversationHubController::class, 'release'])
            ->middleware('throttle:30,1')
            ->name('release');
        Route::post('/{conversation}/invite', [ConversationHubController::class, 'invite'])
            ->middleware('throttle:20,1')
            ->name('invite');
        Route::post('/{conversation}/message', [ConversationHubController::class, 'send'])
            ->middleware('throttle:60,1')
            ->name('send');
    });
