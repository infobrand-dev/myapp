<?php

use App\Modules\TaskManagement\Http\Controllers\TaskTemplateController;
use App\Modules\TaskManagement\Http\Controllers\MemoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:Super-admin|Admin'])
    ->prefix('task-templates')
    ->name('tasktemplates.')
    ->group(function () {
        Route::get('/', [TaskTemplateController::class, 'index'])->name('index');
        Route::get('/list', [TaskTemplateController::class, 'list'])->name('list');
        Route::get('/create', [TaskTemplateController::class, 'create'])->name('create');
        Route::post('/', [TaskTemplateController::class, 'store'])->name('store');
        Route::get('/{template}/edit', [TaskTemplateController::class, 'edit'])->name('edit');
        Route::put('/{template}', [TaskTemplateController::class, 'update'])->name('update');
        Route::delete('/{template}', [TaskTemplateController::class, 'destroy'])->name('destroy');
        Route::get('/{template}', [TaskTemplateController::class, 'show'])->name('show');
    });

Route::middleware(['web', 'auth', 'role:Super-admin|Admin'])
    ->prefix('memos')
    ->name('memos.')
    ->group(function () {
        Route::get('/', [MemoController::class, 'index'])->name('index');
        Route::get('/create', [MemoController::class, 'create'])->name('create');
        Route::post('/', [MemoController::class, 'store'])->name('store');
        Route::get('/{memo}', [MemoController::class, 'show'])->name('show');
        Route::get('/{memo}/edit', [MemoController::class, 'edit'])->name('edit');
        Route::put('/{memo}', [MemoController::class, 'update'])->name('update');
        Route::delete('/{memo}', [MemoController::class, 'destroy'])->name('destroy');

        Route::patch('/tasks/{task}', [MemoController::class, 'updateTask'])->name('tasks.update');
        Route::patch('/subtasks/{subtask}', [MemoController::class, 'updateSubtask'])->name('subtasks.update');
    });
