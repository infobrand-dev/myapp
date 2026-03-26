<?php

use App\Modules\EmailInbox\Http\Controllers\EmailAccountController;
use App\Modules\EmailInbox\Http\Controllers\MailboxController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'permission:email_inbox.view'])
    ->prefix('email-inbox')
    ->name('email-inbox.')
    ->group(function () {
        Route::get('/', [MailboxController::class, 'index'])->name('index');
        Route::get('/accounts/{account}', [MailboxController::class, 'show'])->name('show');
        Route::get('/accounts/{account}/messages/{message}', [MailboxController::class, 'message'])->name('message');
        Route::get('/accounts/{account}/compose', [MailboxController::class, 'compose'])
            ->middleware('permission:email_inbox.send')
            ->name('compose');
        Route::post('/accounts/{account}/send', [MailboxController::class, 'send'])
            ->middleware(['permission:email_inbox.send', 'throttle:30,1'])
            ->name('send');

        Route::middleware('permission:email_inbox.manage_accounts')->group(function () {
            Route::get('/settings/accounts', [EmailAccountController::class, 'index'])->name('accounts.index');
            Route::get('/settings/accounts/create', [EmailAccountController::class, 'create'])->name('accounts.create');
            Route::post('/settings/accounts', [EmailAccountController::class, 'store'])->name('accounts.store');
            Route::get('/settings/accounts/{account}/edit', [EmailAccountController::class, 'edit'])->name('accounts.edit');
            Route::put('/settings/accounts/{account}', [EmailAccountController::class, 'update'])->name('accounts.update');
        });

        Route::post('/accounts/{account}/sync', [EmailAccountController::class, 'sync'])
            ->middleware(['permission:email_inbox.sync', 'throttle:10,1'])
            ->name('accounts.sync');
    });
