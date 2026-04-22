<?php

use App\Modules\Finance\Http\Controllers\AccountingApprovalController;
use App\Modules\Finance\Http\Controllers\AccountingJournalController;
use App\Modules\Finance\Http\Controllers\AccountingPeriodLockController;
use App\Modules\Finance\Http\Controllers\BankReconciliationController;
use App\Modules\Finance\Http\Controllers\ChartOfAccountController;
use App\Modules\Finance\Http\Controllers\FinanceCategoryController;
use App\Modules\Finance\Http\Controllers\FinanceAccountController;
use App\Modules\Finance\Http\Controllers\FinanceTaxDocumentController;
use App\Modules\Finance\Http\Controllers\FinanceTaxRateController;
use App\Modules\Finance\Http\Controllers\FinanceTransactionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'plan.feature:accounting'])
    ->prefix('finance')
    ->name('finance.')
    ->group(function () {
        Route::get('/transactions', [FinanceTransactionController::class, 'index'])->middleware('permission:finance.view')->name('transactions.index');
        Route::get('/transactions/cashbook', [FinanceTransactionController::class, 'cashbook'])->middleware('permission:finance.view')->name('transactions.cashbook');
        Route::get('/transactions/create', [FinanceTransactionController::class, 'create'])->middleware('permission:finance.create')->name('transactions.create');
        Route::post('/transactions', [FinanceTransactionController::class, 'store'])->middleware('permission:finance.create')->name('transactions.store');
        Route::get('/transactions/{transaction}', [FinanceTransactionController::class, 'show'])->middleware('permission:finance.view')->name('transactions.show');
        Route::get('/transactions/{transaction}/edit', [FinanceTransactionController::class, 'edit'])->middleware('permission:finance.create')->name('transactions.edit');
        Route::put('/transactions/{transaction}', [FinanceTransactionController::class, 'update'])->middleware('permission:finance.create')->name('transactions.update');
        Route::delete('/transactions/{transaction}', [FinanceTransactionController::class, 'destroy'])->middleware('permission:finance.create')->name('transactions.destroy');
        Route::get('/chart-of-accounts', [ChartOfAccountController::class, 'index'])->middleware('permission:finance.manage-coa')->name('chart-accounts.index');
        Route::post('/chart-of-accounts', [ChartOfAccountController::class, 'store'])->middleware('permission:finance.manage-coa')->name('chart-accounts.store');
        Route::get('/chart-of-accounts/{chartOfAccount}/edit', [ChartOfAccountController::class, 'edit'])->middleware('permission:finance.manage-coa')->name('chart-accounts.edit');
        Route::put('/chart-of-accounts/{chartOfAccount}', [ChartOfAccountController::class, 'update'])->middleware('permission:finance.manage-coa')->name('chart-accounts.update');
        Route::delete('/chart-of-accounts/{chartOfAccount}', [ChartOfAccountController::class, 'destroy'])->middleware('permission:finance.manage-coa')->name('chart-accounts.destroy');
        Route::get('/taxes', [FinanceTaxRateController::class, 'index'])->middleware('permission:finance.manage-tax')->name('taxes.index');
        Route::post('/taxes', [FinanceTaxRateController::class, 'store'])->middleware('permission:finance.manage-tax')->name('taxes.store');
        Route::get('/taxes/{taxRate}/edit', [FinanceTaxRateController::class, 'edit'])->middleware('permission:finance.manage-tax')->name('taxes.edit');
        Route::put('/taxes/{taxRate}', [FinanceTaxRateController::class, 'update'])->middleware('permission:finance.manage-tax')->name('taxes.update');
        Route::delete('/taxes/{taxRate}', [FinanceTaxRateController::class, 'destroy'])->middleware('permission:finance.manage-tax')->name('taxes.destroy');
        Route::get('/tax-register', [FinanceTaxDocumentController::class, 'index'])->middleware('permission:finance.manage-tax')->name('tax-documents.index');
        Route::get('/tax-register/export', [FinanceTaxDocumentController::class, 'exportRegister'])->middleware('permission:finance.manage-tax')->name('tax-documents.export');
        Route::get('/tax-register/export-efaktur-draft', [FinanceTaxDocumentController::class, 'exportEfakturDraft'])->middleware('permission:finance.manage-tax')->name('tax-documents.export-efaktur-draft');
        Route::post('/tax-register', [FinanceTaxDocumentController::class, 'store'])->middleware('permission:finance.manage-tax')->name('tax-documents.store');
        Route::get('/tax-register/{taxDocument}/edit', [FinanceTaxDocumentController::class, 'edit'])->middleware('permission:finance.manage-tax')->name('tax-documents.edit');
        Route::put('/tax-register/{taxDocument}', [FinanceTaxDocumentController::class, 'update'])->middleware('permission:finance.manage-tax')->name('tax-documents.update');
        Route::get('/journals', [AccountingJournalController::class, 'index'])->middleware('permission:finance.view-journal')->name('journals.index');
        Route::get('/journals/create', [AccountingJournalController::class, 'create'])->middleware('permission:finance.manage-journal')->name('journals.create');
        Route::post('/journals', [AccountingJournalController::class, 'store'])->middleware('permission:finance.manage-journal')->name('journals.store');
        Route::get('/journals/{journal}', [AccountingJournalController::class, 'show'])->middleware('permission:finance.view-journal')->name('journals.show');
        Route::get('/journals/{journal}/edit', [AccountingJournalController::class, 'edit'])->middleware('permission:finance.manage-journal')->name('journals.edit');
        Route::put('/journals/{journal}', [AccountingJournalController::class, 'update'])->middleware('permission:finance.manage-journal')->name('journals.update');
        Route::post('/journals/{journal}/post', [AccountingJournalController::class, 'post'])->middleware('permission:finance.manage-journal')->name('journals.post');
        Route::post('/journals/{journal}/reverse', [AccountingJournalController::class, 'reverse'])->middleware('permission:finance.manage-journal')->name('journals.reverse');
        Route::get('/reconciliations', [BankReconciliationController::class, 'index'])->middleware('permission:finance.manage-reconciliation')->name('reconciliations.index');
        Route::get('/reconciliations/outstanding', [BankReconciliationController::class, 'outstanding'])->middleware('permission:finance.manage-reconciliation')->name('reconciliations.outstanding');
        Route::post('/reconciliations', [BankReconciliationController::class, 'store'])->middleware('permission:finance.manage-reconciliation')->name('reconciliations.store');
        Route::get('/reconciliations/{reconciliation}', [BankReconciliationController::class, 'show'])->middleware('permission:finance.manage-reconciliation')->name('reconciliations.show');
        Route::post('/reconciliations/{reconciliation}/import-statement', [BankReconciliationController::class, 'importStatement'])->middleware('permission:finance.manage-reconciliation')->name('reconciliations.import-statement');
        Route::post('/reconciliations/{reconciliation}/statement-lines/{statementLine}/resolve', [BankReconciliationController::class, 'resolveStatementLine'])->middleware('permission:finance.manage-reconciliation')->name('reconciliations.statement-lines.resolve');
        Route::post('/reconciliations/{reconciliation}/complete', [BankReconciliationController::class, 'complete'])->middleware('permission:finance.manage-reconciliation')->name('reconciliations.complete');
        Route::get('/approvals', [AccountingApprovalController::class, 'index'])->middleware('permission:finance.approve-sensitive-transactions')->name('approvals.index');
        Route::post('/approvals/{approvalRequest}/approve', [AccountingApprovalController::class, 'approve'])->middleware('permission:finance.approve-sensitive-transactions')->name('approvals.approve');
        Route::post('/approvals/{approvalRequest}/reject', [AccountingApprovalController::class, 'reject'])->middleware('permission:finance.approve-sensitive-transactions')->name('approvals.reject');
        Route::get('/period-locks', [AccountingPeriodLockController::class, 'index'])->middleware('permission:finance.manage-period-locks')->name('period-locks.index');
        Route::post('/period-locks', [AccountingPeriodLockController::class, 'store'])->middleware('permission:finance.manage-period-locks')->name('period-locks.store');
        Route::delete('/period-locks/{accountingPeriodLock}', [AccountingPeriodLockController::class, 'destroy'])->middleware('permission:finance.manage-period-locks')->name('period-locks.destroy');

        Route::get('/categories', [FinanceCategoryController::class, 'index'])->middleware('permission:finance.manage-categories')->name('categories.index');
        Route::post('/categories', [FinanceCategoryController::class, 'store'])->middleware('permission:finance.manage-categories')->name('categories.store');
        Route::get('/categories/{category}/edit', [FinanceCategoryController::class, 'edit'])->middleware('permission:finance.manage-categories')->name('categories.edit');
        Route::put('/categories/{category}', [FinanceCategoryController::class, 'update'])->middleware('permission:finance.manage-categories')->name('categories.update');
        Route::delete('/categories/{category}', [FinanceCategoryController::class, 'destroy'])->middleware('permission:finance.manage-categories')->name('categories.destroy');

        Route::get('/accounts', [FinanceAccountController::class, 'index'])->middleware('permission:finance.manage-categories')->name('accounts.index');
        Route::post('/accounts', [FinanceAccountController::class, 'store'])->middleware('permission:finance.manage-categories')->name('accounts.store');
        Route::get('/accounts/{account}/edit', [FinanceAccountController::class, 'edit'])->middleware('permission:finance.manage-categories')->name('accounts.edit');
        Route::put('/accounts/{account}', [FinanceAccountController::class, 'update'])->middleware('permission:finance.manage-categories')->name('accounts.update');
        Route::delete('/accounts/{account}', [FinanceAccountController::class, 'destroy'])->middleware('permission:finance.manage-categories')->name('accounts.destroy');
    });
