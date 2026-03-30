<?php

use App\Modules\TaskManagement\Http\Controllers\MemoController;
use App\Modules\TaskManagement\Http\Controllers\TaskController;
use App\Modules\TaskManagement\Http\Controllers\TaskTemplateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:Super-admin|Admin', 'plan.feature:project_management'])
    ->prefix('tasks')
    ->name('tasks.')
    ->group(function () {
        Route::get('/', [TaskController::class, 'index'])->name('index');
        Route::post('/', [TaskController::class, 'store'])->name('store');
        Route::patch('/{task}/status', [TaskController::class, 'updateStatus'])->name('status');
        Route::delete('/{task}', [TaskController::class, 'destroy'])->name('destroy');
        Route::post('/{task}/subtasks', [TaskController::class, 'storeSubtask'])->name('subtasks.store');
        Route::patch('/subtasks/{subtask}/status', [TaskController::class, 'updateSubtaskStatus'])->name('subtasks.status');
    });

Route::middleware(['web', 'auth', 'role:Super-admin|Admin', 'plan.feature:project_management'])
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

Route::middleware(['web', 'auth', 'role:Super-admin|Admin', 'plan.feature:project_management'])
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
