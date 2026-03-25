<?php

use App\Http\Middleware\VerifyCsrfToken;
use App\Modules\LiveChat\Http\Controllers\LiveChatInboxController;
use App\Modules\LiveChat\Http\Controllers\LiveChatPublicController;
use App\Modules\LiveChat\Http\Controllers\LiveChatWidgetController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->group(function () {
        Route::get('/live-chat/widget/{token}.js', [LiveChatPublicController::class, 'script'])->name('live-chat.widget.script');
        Route::options('/live-chat/api/{token}/bootstrap', [LiveChatPublicController::class, 'options']);
        Route::post('/live-chat/api/{token}/bootstrap', [LiveChatPublicController::class, 'bootstrap'])
            ->middleware('throttle:live-chat-public')
            ->name('live-chat.api.bootstrap');
        Route::options('/live-chat/api/{token}/messages', [LiveChatPublicController::class, 'options']);
        Route::post('/live-chat/api/{token}/typing', [LiveChatPublicController::class, 'typing'])
            ->middleware('throttle:live-chat-public')
            ->name('live-chat.api.typing');
        Route::get('/live-chat/api/{token}/events', [LiveChatPublicController::class, 'events'])
            ->middleware('throttle:live-chat-public')
            ->name('live-chat.api.events');
        Route::get('/live-chat/api/{token}/messages', [LiveChatPublicController::class, 'index'])
            ->middleware('throttle:live-chat-public')
            ->name('live-chat.api.messages.index');
        Route::post('/live-chat/api/{token}/messages', [LiveChatPublicController::class, 'store'])
            ->middleware('throttle:live-chat-public')
            ->name('live-chat.api.messages.store');
    });

Route::middleware(['web', 'auth'])
    ->prefix('live-chat')
    ->name('live-chat.')
    ->group(function () {
        Route::post('/conversations/{conversation}/typing', [LiveChatWidgetController::class, 'typing'])
            ->middleware('throttle:60,1')
            ->name('conversations.typing');
        Route::get('/conversations/{conversation}/status', [LiveChatWidgetController::class, 'status'])
            ->middleware('throttle:60,1')
            ->name('conversations.status');
    });

Route::middleware(['web', 'auth', 'role:Super-admin|Admin'])
    ->prefix('live-chat')
    ->name('live-chat.')
    ->group(function () {
        Route::get('/inbox', [LiveChatInboxController::class, 'index'])->name('inbox.index');
        Route::get('/inbox/{conversation}', [LiveChatInboxController::class, 'show'])->name('inbox.show');
        Route::post('/inbox/{conversation}/reply', [LiveChatInboxController::class, 'reply'])->name('inbox.reply');
        Route::patch('/inbox/{conversation}/close', [LiveChatInboxController::class, 'close'])->name('inbox.close');
    });

Route::middleware(['web', 'auth', 'role:Super-admin'])
    ->prefix('live-chat')
    ->name('live-chat.')
    ->group(function () {
        Route::get('/widgets', [LiveChatWidgetController::class, 'index'])->name('widgets.index');
        Route::get('/widgets/create', [LiveChatWidgetController::class, 'create'])->name('widgets.create');
        Route::post('/widgets', [LiveChatWidgetController::class, 'store'])->name('widgets.store');
        Route::get('/widgets/{widget}/edit', [LiveChatWidgetController::class, 'edit'])->name('widgets.edit');
        Route::put('/widgets/{widget}', [LiveChatWidgetController::class, 'update'])->name('widgets.update');
    });
