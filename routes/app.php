<?php

use App\Http\Controllers\AccountingUiModeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationPushSubscriptionController;
use App\Http\Controllers\PlatformDomainController;
use App\Http\Controllers\PlatformAffiliateController;
use App\Http\Controllers\PlatformOwnerController;
use App\Http\Controllers\PlatformStorageController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\Settings\PaymentGatewaySettingsController;
use App\Http\Controllers\Settings\TenantCustomDomainController;
use App\Http\Controllers\Settings\ShippingProviderSettingsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StoredFileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserInvitationController;
use App\Http\Controllers\UserPresenceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Workspace App Routes
|--------------------------------------------------------------------------
|
| Authenticated shell routes for tenant workspaces and the platform-admin
| control plane. Public platform billing links live in routes/platform-public.php.
|
*/

Route::any('/install/{any?}', function () {
    return redirect('/login');
})->where('any', '.*');

Route::middleware(['auth', 'verified', '2fa', 'platform.admin', \App\Http\Middleware\ResolveCompanyContext::class, \App\Http\Middleware\ResolveBranchContext::class])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/settings/accounting-ui-mode', [AccountingUiModeController::class, 'store'])->name('settings.accounting-ui-mode');
    Route::middleware('permission:notifications.view')->prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/preview', [NotificationController::class, 'preview'])->name('preview');
        Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->name('unread-count');
        Route::post('/{recipient}/read', [NotificationController::class, 'markRead'])->name('read');
        Route::post('/{recipient}/unread', [NotificationController::class, 'markUnread'])->name('unread');
        Route::post('/{recipient}/dismiss', [NotificationController::class, 'dismiss'])->name('dismiss');
        Route::post('/{recipient}/archive', [NotificationController::class, 'archive'])->name('archive');
        Route::post('/push-subscriptions', [NotificationPushSubscriptionController::class, 'store'])->name('push-subscriptions.store');
        Route::delete('/push-subscriptions', [NotificationPushSubscriptionController::class, 'destroy'])->name('push-subscriptions.destroy');
    });

    Route::prefix('platform')->name('platform.')->group(function () {
        Route::get('/', [PlatformOwnerController::class, 'dashboard'])->name('dashboard');
        Route::get('/tenants', [PlatformOwnerController::class, 'tenants'])->name('tenants.index');
        Route::get('/tenants/{tenant}', [PlatformOwnerController::class, 'tenant'])->name('tenants.show');
        Route::post('/tenants/{tenant}/status', [PlatformOwnerController::class, 'updateTenantStatus'])->name('tenants.status');
        Route::post('/tenants/{tenant}/subscriptions/{subscription}/cancel', [PlatformOwnerController::class, 'cancelActivePlan'])->name('tenants.subscriptions.cancel');
        Route::post('/tenants/{tenant}/notes', [PlatformOwnerController::class, 'updateTenantNotes'])->name('tenants.notes');
        Route::post('/tenants/{tenant}/ai-credits', [PlatformOwnerController::class, 'topUpAiCredits'])->name('tenants.ai-credits.store');
        Route::post('/tenants/{tenant}/byo-ai-addon', [PlatformOwnerController::class, 'updateByoAiAddon'])->name('tenants.byo-ai-addon.update');
        Route::post('/tenants/{tenant}/byo-ai-requests/{requestModel}', [PlatformOwnerController::class, 'reviewByoAiRequest'])->name('tenants.byo-ai-requests.review');
        Route::post('/ai-credit-pricing', [PlatformOwnerController::class, 'updateAiCreditPricing'])->name('ai-credit-pricing.update');
        Route::get('/promos', [PlatformOwnerController::class, 'promos'])->name('promos.index');
        Route::post('/promos', [PlatformOwnerController::class, 'storePromo'])->name('promos.store');
        Route::put('/promos/{promo}', [PlatformOwnerController::class, 'updatePromo'])->name('promos.update');
        Route::post('/tenants/{tenant}/assign-plan', [PlatformOwnerController::class, 'assignPlan'])->name('tenants.assign-plan');
        Route::post('/tenants/{tenant}/orders', [PlatformOwnerController::class, 'createOrder'])->name('tenants.orders.store');
        Route::get('/plans', [PlatformOwnerController::class, 'plans'])->name('plans.index');
        Route::get('/plans/{plan}/edit', [PlatformOwnerController::class, 'editPlan'])->name('plans.edit');
        Route::put('/plans/{plan}', [PlatformOwnerController::class, 'updatePlan'])->name('plans.update');
        Route::get('/go-live', [PlatformOwnerController::class, 'golive'])->name('golive');
        Route::get('/domains', [PlatformDomainController::class, 'index'])->name('domains.index');
        Route::put('/domains/settings', [PlatformDomainController::class, 'updateSettings'])->name('domains.settings.update');
        Route::post('/domains/{tenantDomain}/sync', [PlatformDomainController::class, 'sync'])->name('domains.sync');
        Route::get('/domains/audit', [PlatformDomainController::class, 'audit'])->name('domains.audit');
        Route::get('/storage', [PlatformStorageController::class, 'index'])->name('storage.index');
        Route::post('/storage', [PlatformStorageController::class, 'store'])->name('storage.store');
        Route::put('/storage/{profile}', [PlatformStorageController::class, 'update'])->name('storage.update');
        Route::post('/storage/{profile}/toggle', [PlatformStorageController::class, 'toggle'])->name('storage.toggle');
        Route::get('/affiliates', [PlatformAffiliateController::class, 'index'])->name('affiliates.index');
        Route::get('/affiliate-payouts', [PlatformAffiliateController::class, 'payouts'])->name('affiliates.payouts');
        Route::post('/affiliates', [PlatformAffiliateController::class, 'store'])->name('affiliates.store');
        Route::get('/affiliates/{affiliate}', [PlatformAffiliateController::class, 'show'])->name('affiliates.show');
        Route::post('/affiliates/{affiliate}/referrals/{referral}/payout', [PlatformAffiliateController::class, 'updatePayoutStatus'])->name('affiliates.referrals.payout');
        Route::get('/orders', [PlatformOwnerController::class, 'orders'])->name('orders.index');
        Route::post('/orders/{order}/mark-paid', [PlatformOwnerController::class, 'markOrderPaid'])->name('orders.mark-paid');
        Route::post('/orders/{order}/void', [PlatformOwnerController::class, 'voidOrder'])->name('orders.void');
        Route::post('/orders/{order}/cancel', [PlatformOwnerController::class, 'cancelOrder'])->name('orders.cancel');
        Route::post('/orders/{order}/invoice', [PlatformOwnerController::class, 'createInvoice'])->name('orders.invoice');
        Route::get('/invoices/{invoice}', [PlatformOwnerController::class, 'invoice'])->name('invoices.show');
        Route::post('/invoices/{invoice}/resend', [PlatformOwnerController::class, 'resendInvoice'])->name('invoices.resend');
        Route::post('/invoices/{invoice}/payments', [PlatformOwnerController::class, 'recordPayment'])->name('invoices.payments.store');
    });

    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::get('/files/{storedFile}/download', [StoredFileController::class, 'download'])->name('stored-files.download');
    Route::get('/files/{storedFile}/preview', [StoredFileController::class, 'preview'])->name('stored-files.preview');
    Route::get('/files/legacy-download', [StoredFileController::class, 'legacyDownload'])->middleware('signed')->name('stored-files.legacy-download');

    Route::middleware('permission:settings.view')->group(function () {
        Route::get('/settings', [SettingsController::class, 'show'])->defaults('section', 'general')->name('settings.general');
        Route::get('/settings/company', [SettingsController::class, 'show'])->defaults('section', 'company')->name('settings.company');
        Route::get('/settings/branch', [SettingsController::class, 'show'])->defaults('section', 'branch')->name('settings.branch');
        Route::get('/settings/documents', [SettingsController::class, 'show'])->defaults('section', 'documents')->name('settings.documents');
        Route::get('/settings/subscription', [SettingsController::class, 'show'])->defaults('section', 'subscription')->name('settings.subscription');
        Route::get('/settings/addons', [SettingsController::class, 'show'])->defaults('section', 'addons')->name('settings.addons');
        Route::get('/settings/access', [SettingsController::class, 'show'])->defaults('section', 'access')->name('settings.access');
        Route::get('/settings/modules', [SettingsController::class, 'show'])->defaults('section', 'modules')->name('settings.modules');
        Route::get('/settings/transactional-email', [SettingsController::class, 'show'])->defaults('section', 'transactional-email')->name('settings.transactional-email');
        Route::get('/settings/payment-gateway', [SettingsController::class, 'show'])->defaults('section', 'payment-gateway')->name('settings.payment-gateway');
        Route::get('/settings/shipping-provider', [SettingsController::class, 'show'])->defaults('section', 'shipping-provider')->name('settings.shipping-provider');
        Route::get('/settings/custom-domains', [SettingsController::class, 'show'])->defaults('section', 'custom-domains')->name('settings.custom-domains');

        Route::post('/settings/company/switch/{company}', [SettingsController::class, 'switchCompany'])->name('settings.company.switch');
        Route::post('/settings/branch/switch/{branch}', [SettingsController::class, 'switchBranch'])->name('settings.branch.switch');
        Route::post('/settings/branch/clear', [SettingsController::class, 'clearBranch'])->name('settings.branch.clear');
    });

    Route::middleware('permission:settings.manage')->group(function () {
        Route::post('/settings/company', [SettingsController::class, 'storeCompany'])->name('settings.company.store');
        Route::put('/settings/company/{company}', [SettingsController::class, 'updateCompany'])->name('settings.company.update');
        Route::post('/settings/company/{company}/activate', [SettingsController::class, 'activateCompany'])->name('settings.company.activate');

        Route::post('/settings/branch', [SettingsController::class, 'storeBranch'])->name('settings.branch.store');
        Route::put('/settings/branch/{branch}', [SettingsController::class, 'updateBranch'])->name('settings.branch.update');
        Route::post('/settings/branch/{branch}/activate', [SettingsController::class, 'activateBranch'])->name('settings.branch.activate');

        Route::put('/settings/documents', [SettingsController::class, 'saveDocuments'])->name('settings.documents.save');
        Route::put('/settings/general', [SettingsController::class, 'saveGeneral'])->name('settings.general.save');
        Route::put('/settings/transactional-email', [SettingsController::class, 'saveTransactionalEmail'])->name('settings.transactional-email.save');
        Route::put('/settings/payment-gateway', [PaymentGatewaySettingsController::class, 'update'])->name('settings.payment-gateway.save');
        Route::put('/settings/shipping-provider', [ShippingProviderSettingsController::class, 'update'])->name('settings.shipping-provider.save');
        Route::post('/settings/transactional-email/test', [SettingsController::class, 'sendTransactionalEmailTest'])->name('settings.transactional-email.test');
        Route::post('/settings/addons/byo-ai-request', [SettingsController::class, 'requestByoAi'])->name('settings.addons.byo-ai-request');
        Route::post('/settings/custom-domains', [TenantCustomDomainController::class, 'store'])->middleware('plan.feature:custom_domains')->name('settings.custom-domains.store');
        Route::post('/settings/custom-domains/{tenantDomain}/sync', [TenantCustomDomainController::class, 'sync'])->middleware('plan.feature:custom_domains')->name('settings.custom-domains.sync');
        Route::post('/settings/custom-domains/{tenantDomain}/promote', [TenantCustomDomainController::class, 'promote'])->middleware('plan.feature:custom_domains')->name('settings.custom-domains.promote');
        Route::delete('/settings/custom-domains/{tenantDomain}', [TenantCustomDomainController::class, 'destroy'])->middleware('plan.feature:custom_domains')->name('settings.custom-domains.destroy');
    });
});

Route::middleware(['auth', 'verified', '2fa', 'platform.admin', \App\Http\Middleware\ResolveCompanyContext::class, \App\Http\Middleware\ResolveBranchContext::class])->prefix('presence')->name('presence.')->group(function () {
    Route::post('/heartbeat', [UserPresenceController::class, 'heartbeat'])->name('heartbeat');
    Route::post('/status', [UserPresenceController::class, 'setStatus'])->name('status');
});

Route::middleware(['auth', '2fa', 'platform.admin', \App\Http\Middleware\ResolveCompanyContext::class, \App\Http\Middleware\ResolveBranchContext::class])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->middleware('permission:users.view')->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->middleware('permission:users.create')->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->middleware('permission:users.create')->name('users.store');
    Route::post('/users/invitations', [UserInvitationController::class, 'store'])->middleware('permission:users.create')->name('users.invitations.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->middleware('permission:users.update')->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->middleware('permission:users.update')->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('permission:users.delete')->name('users.destroy');

    Route::get('/roles', [RoleController::class, 'index'])->middleware('permission:roles.view')->name('roles.index');
    Route::get('/roles/create', [RoleController::class, 'create'])->middleware('permission:roles.create')->name('roles.create');
    Route::post('/roles', [RoleController::class, 'store'])->middleware('permission:roles.create')->name('roles.store');
    Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->middleware('permission:roles.update')->name('roles.edit');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->middleware('permission:roles.update')->name('roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:roles.delete')->name('roles.destroy');

    Route::get('/modules', [ModuleController::class, 'index'])->middleware('permission:modules.view')->name('modules.index');
    Route::post('/modules/{slug}/install', [ModuleController::class, 'install'])->middleware('permission:modules.install')->name('modules.install');
    Route::post('/modules/{slug}/activate', [ModuleController::class, 'activate'])->middleware('permission:modules.activate')->name('modules.activate');
    Route::post('/modules/{slug}/db-update', [ModuleController::class, 'runDbUpdate'])->middleware('permission:modules.activate')->name('modules.db-update');
    Route::post('/modules/{slug}/migrations/{migration}', [ModuleController::class, 'runSingleMigration'])->middleware('permission:modules.activate')->name('modules.migrations.run');
    Route::post('/modules/{slug}/deactivate', [ModuleController::class, 'deactivate'])->middleware('permission:modules.deactivate')->name('modules.deactivate');
    Route::post('/modules/bulk', [ModuleController::class, 'bulk'])->middleware('permission:modules.view')->name('modules.bulk');
});
