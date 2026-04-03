<?php

use App\Modules\Contacts\Http\Controllers\ContactController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'plan.feature:crm,commerce', 'permission:contacts.view'])
    ->prefix('contacts')
    ->name('contacts.')
    ->group(function () {
        Route::get('/', [ContactController::class, 'index'])->name('index');
        Route::get('/merge-candidates', [ContactController::class, 'mergeCandidates'])->middleware('permission:contacts.merge')->name('merge-candidates');
        Route::post('/merge', [ContactController::class, 'merge'])->middleware('permission:contacts.merge')->name('merge');
        Route::get('/import', [ContactController::class, 'importPage'])->middleware('permission:contacts.import')->name('import-page');
        Route::get('/import-template/{format}', [ContactController::class, 'downloadTemplate'])->middleware('permission:contacts.import')->name('import-template');
        Route::post('/import', [ContactController::class, 'import'])->middleware('permission:contacts.import')->name('import');
        Route::get('/create', [ContactController::class, 'create'])->middleware('permission:contacts.create')->name('create');
        Route::post('/', [ContactController::class, 'store'])->middleware('permission:contacts.create')->name('store');
        Route::delete('/bulk-destroy', [ContactController::class, 'bulkDestroy'])->middleware('permission:contacts.delete')->name('bulk-destroy');
        Route::get('/{contact}', [ContactController::class, 'show'])->name('show');
        Route::get('/{contact}/edit', [ContactController::class, 'edit'])->middleware('permission:contacts.update')->name('edit');
        Route::post('/{contact}/notes-from-conversation', [ContactController::class, 'updateNotesFromConversation'])->middleware('permission:contacts.update')->name('notes-from-conversation');
        Route::put('/{contact}', [ContactController::class, 'update'])->middleware('permission:contacts.update')->name('update');
        Route::delete('/{contact}', [ContactController::class, 'destroy'])->middleware('permission:contacts.delete')->name('destroy');
    });
