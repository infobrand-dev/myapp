<?php

use App\Http\Controllers\RoleController;
use App\Http\Controllers\TenantOnboardingController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AffiliateProgramController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\PlatformOwnerController;
use App\Http\Controllers\PlatformBillingMidtransController;
use App\Http\Controllers\PlatformAffiliateController;
use App\Http\Controllers\UserPresenceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::post('locale/switch', [App\Http\Controllers\LocaleController::class, 'switch'])->name('locale.switch');

Route::get('/', LandingPageController::class)->name('landing');
Route::get('/affiliate-program', AffiliateProgramController::class)->name('affiliate.program');
Route::get('/aff/{slug}', [LandingPageController::class, 'affiliateRedirect'])->name('affiliate.redirect');
Route::get('/workspace', [LandingPageController::class, 'workspaceFinder'])->name('workspace.finder');
Route::post('/workspace', [LandingPageController::class, 'redirectToWorkspaceLogin'])->name('workspace.redirect');

// Health check — no auth, no session, no CSRF. Used by uptime monitors and load balancers.
Route::get('/health', function () {
    $checks = [];

    // Database
    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        $checks['database'] = 'ok';
    } catch (\Throwable $e) {
        $checks['database'] = 'error';
    }

    // Cache
    try {
        \Illuminate\Support\Facades\Cache::put('_health', 1, 5);
        $checks['cache'] = 'ok';
    } catch (\Throwable $e) {
        $checks['cache'] = 'error';
    }

    $healthy = ! in_array('error', $checks, true);

    return response()->json([
        'status'  => $healthy ? 'ok' : 'degraded',
        'checks'  => $checks,
        'version' => config('app.version', '1.0.0'),
    ], $healthy ? 200 : 503);
})->withoutMiddleware([
    \App\Http\Middleware\EnsureInstalled::class,
    \App\Http\Middleware\ResolveTenantContext::class,
    \App\Http\Middleware\ResolveCompanyContext::class,
    \App\Http\Middleware\ResolveBranchContext::class,
    \App\Http\Middleware\VerifyCsrfToken::class,
    \App\Http\Middleware\EncryptCookies::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
])->name('health');

// SaaS tenant self-registration — only reachable on the apex/root domain (no subdomain)
Route::get('/onboarding', [TenantOnboardingController::class, 'create'])->middleware('throttle:web')->name('onboarding.create');
Route::post('/onboarding', [TenantOnboardingController::class, 'store'])->middleware('throttle:10,5')->name('onboarding.store');

Route::any('/install/{any?}', function () {
    return redirect('/login');
})->where('any', '.*');

Route::middleware(['auth', 'verified', '2fa', 'platform.admin', \App\Http\Middleware\ResolveCompanyContext::class, \App\Http\Middleware\ResolveBranchContext::class])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('platform')->name('platform.')->group(function () {
        Route::get('/', [PlatformOwnerController::class, 'dashboard'])->name('dashboard');
        Route::get('/tenants', [PlatformOwnerController::class, 'tenants'])->name('tenants.index');
        Route::get('/tenants/{tenant}', [PlatformOwnerController::class, 'tenant'])->name('tenants.show');
        Route::post('/tenants/{tenant}/status', [PlatformOwnerController::class, 'updateTenantStatus'])->name('tenants.status');
        Route::post('/tenants/{tenant}/notes', [PlatformOwnerController::class, 'updateTenantNotes'])->name('tenants.notes');
        Route::post('/tenants/{tenant}/ai-credits', [PlatformOwnerController::class, 'topUpAiCredits'])->name('tenants.ai-credits.store');
        Route::post('/ai-credit-pricing', [PlatformOwnerController::class, 'updateAiCreditPricing'])->name('ai-credit-pricing.update');
        Route::post('/tenants/{tenant}/assign-plan', [PlatformOwnerController::class, 'assignPlan'])->name('tenants.assign-plan');
        Route::post('/tenants/{tenant}/orders', [PlatformOwnerController::class, 'createOrder'])->name('tenants.orders.store');
        Route::get('/plans', [PlatformOwnerController::class, 'plans'])->name('plans.index');
        Route::get('/plans/{plan}/edit', [PlatformOwnerController::class, 'editPlan'])->name('plans.edit');
        Route::put('/plans/{plan}', [PlatformOwnerController::class, 'updatePlan'])->name('plans.update');
        Route::get('/go-live', [PlatformOwnerController::class, 'golive'])->name('golive');
        Route::get('/affiliates', [PlatformAffiliateController::class, 'index'])->name('affiliates.index');
        Route::get('/affiliate-payouts', [PlatformAffiliateController::class, 'payouts'])->name('affiliates.payouts');
        Route::post('/affiliates', [PlatformAffiliateController::class, 'store'])->name('affiliates.store');
        Route::get('/affiliates/{affiliate}', [PlatformAffiliateController::class, 'show'])->name('affiliates.show');
        Route::post('/affiliates/{affiliate}/referrals/{referral}/payout', [PlatformAffiliateController::class, 'updatePayoutStatus'])->name('affiliates.referrals.payout');
        Route::get('/orders', [PlatformOwnerController::class, 'orders'])->name('orders.index');
        Route::post('/orders/{order}/mark-paid', [PlatformOwnerController::class, 'markOrderPaid'])->name('orders.mark-paid');
        Route::post('/orders/{order}/cancel', [PlatformOwnerController::class, 'cancelOrder'])->name('orders.cancel');
        Route::post('/orders/{order}/invoice', [PlatformOwnerController::class, 'createInvoice'])->name('orders.invoice');
        Route::get('/invoices/{invoice}', [PlatformOwnerController::class, 'invoice'])->name('invoices.show');
        Route::post('/invoices/{invoice}/resend', [PlatformOwnerController::class, 'resendInvoice'])->name('invoices.resend');
        Route::post('/invoices/{invoice}/payments', [PlatformOwnerController::class, 'recordPayment'])->name('invoices.payments.store');
    });

    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');

    Route::middleware('permission:settings.view')->group(function () {
        Route::get('/settings', [SettingsController::class, 'show'])->defaults('section', 'general')->name('settings.general');
        Route::get('/settings/company', [SettingsController::class, 'show'])->defaults('section', 'company')->name('settings.company');
        Route::get('/settings/branch', [SettingsController::class, 'show'])->defaults('section', 'branch')->name('settings.branch');
        Route::get('/settings/documents', [SettingsController::class, 'show'])->defaults('section', 'documents')->name('settings.documents');
        Route::get('/settings/subscription', [SettingsController::class, 'show'])->defaults('section', 'subscription')->name('settings.subscription');
        Route::get('/settings/access', [SettingsController::class, 'show'])->defaults('section', 'access')->name('settings.access');
        Route::get('/settings/modules', [SettingsController::class, 'show'])->defaults('section', 'modules')->name('settings.modules');

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
    });
});

Route::post('/platform/billing/midtrans/webhook', [PlatformBillingMidtransController::class, 'notification'])
    ->withoutMiddleware([
        \App\Http\Middleware\VerifyCsrfToken::class,
        \App\Http\Middleware\ResolveTenantFromSubdomain::class,
        \App\Http\Middleware\ResolveTenantContext::class,
        \App\Http\Middleware\ResolveCompanyContext::class,
        \App\Http\Middleware\ResolveBranchContext::class,
    ])
    ->name('platform.billing.midtrans.webhook');

Route::get('/platform/public/invoices/{invoice}', [PlatformOwnerController::class, 'publicInvoice'])
    ->middleware('signed')
    ->withoutMiddleware([
        \App\Http\Middleware\ResolveTenantFromSubdomain::class,
        \App\Http\Middleware\ResolveTenantContext::class,
        \App\Http\Middleware\ResolveCompanyContext::class,
        \App\Http\Middleware\ResolveBranchContext::class,
    ])
    ->name('platform.invoices.public');

Route::post('/platform/public/invoices/{invoice}/midtrans/checkout', [PlatformBillingMidtransController::class, 'checkout'])
    ->middleware('signed')
    ->withoutMiddleware([
        \App\Http\Middleware\VerifyCsrfToken::class,
        \App\Http\Middleware\ResolveTenantFromSubdomain::class,
        \App\Http\Middleware\ResolveTenantContext::class,
        \App\Http\Middleware\ResolveCompanyContext::class,
        \App\Http\Middleware\ResolveBranchContext::class,
    ])
    ->name('platform.invoices.public.midtrans.checkout');

Route::middleware(['auth', 'verified', '2fa', 'platform.admin', \App\Http\Middleware\ResolveCompanyContext::class, \App\Http\Middleware\ResolveBranchContext::class])->prefix('presence')->name('presence.')->group(function () {
    Route::post('/heartbeat', [UserPresenceController::class, 'heartbeat'])->name('heartbeat');
    Route::post('/status', [UserPresenceController::class, 'setStatus'])->name('status');
});

Route::middleware(['auth', '2fa', 'platform.admin', \App\Http\Middleware\ResolveCompanyContext::class, \App\Http\Middleware\ResolveBranchContext::class])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->middleware('permission:users.view')->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->middleware('permission:users.create')->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->middleware('permission:users.create')->name('users.store');
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
    Route::post('/modules/{slug}/deactivate', [ModuleController::class, 'deactivate'])->middleware('permission:modules.deactivate')->name('modules.deactivate');
});

require __DIR__ . '/auth.php';
