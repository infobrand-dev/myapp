<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Chatbot\Http\Controllers\ChatbotAccountController;
use App\Modules\Chatbot\Http\Controllers\ConversationBotController;
use App\Modules\Chatbot\Http\Controllers\ChatbotKnowledgeController;
use App\Modules\Chatbot\Http\Controllers\ChatbotPlaygroundController;

Route::middleware(['web', 'auth'])
    ->prefix('chatbot')
    ->name('chatbot.')
    ->group(function () {
        Route::middleware('role:Super-admin')->group(function () {
            Route::resource('accounts', ChatbotAccountController::class)->except(['show']);
            Route::get('accounts/{account}/knowledge', [ChatbotKnowledgeController::class, 'index'])->name('knowledge.index');
            Route::get('accounts/{account}/knowledge/create', [ChatbotKnowledgeController::class, 'create'])->name('knowledge.create');
            Route::post('accounts/{account}/knowledge', [ChatbotKnowledgeController::class, 'store'])->name('knowledge.store');
            Route::get('accounts/{account}/knowledge/{document}/edit', [ChatbotKnowledgeController::class, 'edit'])->name('knowledge.edit');
            Route::put('accounts/{account}/knowledge/{document}', [ChatbotKnowledgeController::class, 'update'])->name('knowledge.update');
            Route::delete('accounts/{account}/knowledge/{document}', [ChatbotKnowledgeController::class, 'destroy'])->name('knowledge.destroy');
        });

        Route::get('playground', [ChatbotPlaygroundController::class, 'index'])->name('playground.index');
        Route::get('playground/{session}', [ChatbotPlaygroundController::class, 'show'])->name('playground.show');
        Route::post('playground/send', [ChatbotPlaygroundController::class, 'send'])
            ->middleware('throttle:20,1')
            ->name('playground.send');
        Route::post('conversations/{conversation}/pause-bot', [ConversationBotController::class, 'pause'])
            ->name('conversations.pause-bot');
        Route::post('conversations/{conversation}/resume-bot', [ConversationBotController::class, 'resume'])
            ->name('conversations.resume-bot');
    });
