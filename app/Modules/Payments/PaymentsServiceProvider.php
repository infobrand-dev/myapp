<?php

namespace App\Modules\Payments;

use App\Modules\Payments\Actions\CreatePaymentAction;
use App\Modules\Payments\Actions\RecalculatePaymentSummaryAction;
use App\Modules\Payments\Actions\ValidatePayableTransactionAction;
use App\Modules\Payments\Actions\VoidPaymentAction;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Policies\PaymentPolicy;
use App\Modules\Payments\Repositories\PaymentRepository;
use App\Modules\Payments\Services\PaymentLookupService;
use App\Modules\Payments\Services\PaymentNumberService;
use App\Modules\Payments\Services\PaymentSummaryService;
use App\Support\RegistersModuleRoutes;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PaymentsServiceProvider extends ServiceProvider
{
    use RegistersModuleRoutes;

    public const PERMISSIONS = [
        'payments.view',
        'payments.view_all',
        'payments.view_own',
        'payments.create',
        'payments.assign_receiver',
        'payments.void',
        'payments.manage_methods',
        'payments.print',
        'payments.export',
    ];

    public const DEFAULT_ROLE_PERMISSIONS = [
        'Super-admin' => self::PERMISSIONS,
        'Admin' => [
            'payments.view',
            'payments.view_own',
            'payments.create',
            'payments.void',
            'payments.print',
        ],
        'Cashier' => [
            'payments.view',
            'payments.view_own',
            'payments.create',
            'payments.print',
        ],
        'Finance Staff' => self::PERMISSIONS,
    ];

    public function register(): void
    {
        $this->app->singleton(PaymentRepository::class);
        $this->app->singleton(PaymentLookupService::class);
        $this->app->singleton(PaymentNumberService::class);
        $this->app->singleton(PaymentSummaryService::class);
        $this->app->singleton(ValidatePayableTransactionAction::class);
        $this->app->singleton(RecalculatePaymentSummaryAction::class);
        $this->app->singleton(CreatePaymentAction::class);
        $this->app->singleton(VoidPaymentAction::class);
    }

    public function boot(): void
    {
        $this->registerModuleRoutes([__DIR__ . '/routes/web.php']);
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'payments');
        $this->loadTranslationsFrom(__DIR__ . '/resources/lang', 'payments');
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');

        Gate::policy(Payment::class, PaymentPolicy::class);
        $this->ensurePermissions();
    }

    private function ensurePermissions(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        foreach (self::PERMISSIONS as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
