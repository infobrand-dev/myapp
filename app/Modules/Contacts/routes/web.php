<?php

use App\Modules\Contacts\Http\Controllers\ContactController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:Super-admin|Admin'])
    ->prefix('contacts')
    ->name('contacts.')
    ->group(function () {
        Route::get('/', [ContactController::class, 'index'])->name('index');
        Route::get('/merge-candidates', [ContactController::class, 'mergeCandidates'])->name('merge-candidates');
        Route::post('/merge', [ContactController::class, 'merge'])->name('merge');
        Route::get('/import', [ContactController::class, 'importPage'])->name('import-page');
        Route::get('/import-template/{format}', [ContactController::class, 'downloadTemplate'])->name('import-template');
        Route::post('/import', [ContactController::class, 'import'])->name('import');
        Route::get('/create', [ContactController::class, 'create'])->name('create');
        Route::post('/', [ContactController::class, 'store'])->name('store');
        Route::get('/{contact}', [ContactController::class, 'show'])->name('show');
        Route::get('/{contact}/edit', [ContactController::class, 'edit'])->name('edit');
        Route::put('/{contact}', [ContactController::class, 'update'])->name('update');
        Route::delete('/{contact}', [ContactController::class, 'destroy'])->name('destroy');
    });
