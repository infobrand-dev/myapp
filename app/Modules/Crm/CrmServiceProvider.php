<?php

namespace App\Modules\Crm;

use App\Modules\Contacts\Models\Contact;
use App\Modules\Crm\Models\CrmFollowUpTask;
use App\Modules\Crm\Models\CrmLead;
use App\Modules\Crm\Support\CrmCustomer360Bridge;
use App\Modules\Crm\Support\Customer360TimelineBuilder;
use App\Support\BooleanQuery;
use App\Support\HookManager;
use App\Support\PlanFeature;
use App\Support\PlanLimit;
use App\Support\RegistersModuleRoutes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class CrmServiceProvider extends ServiceProvider
{
    use RegistersModuleRoutes;

    public const PLAN_LIMIT_MODELS = [
        PlanLimit::CRM_PIPELINES => ['table' => 'crm_pipelines', 'model' => \App\Modules\Crm\Models\CrmPipeline::class],
        PlanLimit::CRM_CUSTOM_STAGES => ['table' => 'crm_pipeline_stages', 'model' => \App\Modules\Crm\Models\CrmPipelineStage::class],
        PlanLimit::CRM_ACTIVE_DEALS => ['table' => 'crm_leads', 'model' => \App\Modules\Crm\Models\CrmLead::class],
    ];

    public const PERMISSIONS = [
        'crm.view',
        'crm.create',
        'crm.update',
        'crm.delete',
        'crm.assign',
        'crm.manage_pipeline',
        'crm.export',
        'crm.view_all',
    ];

    public const DEFAULT_ROLE_PERMISSIONS = [
        'Super-admin' => self::PERMISSIONS,
        'Admin' => self::PERMISSIONS,
        'Customer Service' => [
            'crm.view',
            'crm.create',
            'crm.update',
            'crm.assign',
        ],
        'Sales' => [
            'crm.view',
            'crm.create',
            'crm.update',
            'crm.assign',
        ],
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerModuleRoutes([__DIR__ . '/routes/web.php', __DIR__ . '/routes/api.php']);
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'crm');
        $this->loadMigrationsFrom(\App\Support\ModulePath::migrationDirectory(__DIR__) ?? (__DIR__ . '/Database/Migrations'));
        $this->ensurePermissions();
        $this->registerContactHooks();
        $this->registerDashboardHooks();
        $this->registerIntegrationHooks();
    }

    private function ensurePermissions(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $created = false;

        foreach (self::PERMISSIONS as $permission) {
            $record = Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);

            $created = $created || $record->wasRecentlyCreated;
        }

        if ($created) {
            app(\App\Support\TenantRoleProvisioner::class)->ensureForAllTenants();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function registerDashboardHooks(): void
    {
        /** @var HookManager $hooks */
        $hooks = $this->app->make(HookManager::class);

        $hooks->register('dashboard.overview.cards', 'crm.dashboard.card', function (): string {
            $user = auth()->user();

            if (!$user || !Schema::hasTable('crm_leads')) {
                return '';
            }

            if (!app(\App\Support\TenantPlanManager::class)->hasFeature(PlanFeature::CRM, \App\Support\TenantContext::currentId())) {
                return '';
            }

            $baseQuery = CrmLead::query()
                ->where('tenant_id', \App\Support\TenantContext::currentId());
            BooleanQuery::apply($baseQuery, 'is_archived', false);

            if (!$user->hasAnyRole(['Super-admin', 'Admin'])) {
                $baseQuery->where('owner_user_id', $user->id);
            }

            $metrics = [
                'active'          => (clone $baseQuery)->whereNotIn('stage', ['won', 'lost'])->count(),
                'follow_up_due'   => (clone $baseQuery)->whereNotIn('stage', ['won', 'lost'])->whereNotNull('next_follow_up_at')->where('next_follow_up_at', '<=', now()->endOfDay())->count(),
                'won_this_month'  => (clone $baseQuery)->where('stage', 'won')->whereMonth('won_at', now()->month)->whereYear('won_at', now()->year)->count(),
                'audience'        => $user->hasAnyRole(['Super-admin', 'Admin']) ? 'global' : 'personal',
            ];

            return view('crm::dashboard.card', compact('metrics'))->render();
        });
    }

    private function registerContactHooks(): void
    {
        if (!class_exists(Contact::class)) {
            return;
        }

        /** @var HookManager $hooks */
        $hooks = $this->app->make(HookManager::class);

        $renderAction = function (array $context): string {
            $user = auth()->user();

            if (!Schema::hasTable('crm_leads')
                || !$user
                || !app(\App\Support\TenantPlanManager::class)->hasFeature(PlanFeature::CRM, \App\Support\TenantContext::currentId())
                || !$user->can('crm.view')) {
                return '';
            }

            /** @var Contact|null $contact */
            $contact = $context['contact'] ?? null;
            if (!$contact) {
                return '';
            }

            $lead = CrmLead::query()
                ->where('tenant_id', $contact->tenant_id)
                ->where('contact_id', $contact->id)
                ->latest('id')
                ->first();

            return view('crm::hooks.contact-action', compact('contact', 'lead'))->render();
        };

        $renderCustomer360 = function (array $context): string {
            $user = auth()->user();

            if (!Schema::hasTable('crm_leads')
                || !$user
                || !app(\App\Support\TenantPlanManager::class)->hasFeature(PlanFeature::CRM, \App\Support\TenantContext::currentId())
                || !$user->can('crm.view')) {
                return '';
            }

            /** @var Contact|null $contact */
            $contact = $context['contact'] ?? null;
            if (!$contact) {
                return '';
            }

            $customer360 = app(Customer360TimelineBuilder::class)->build($contact);

            return view('crm::hooks.customer-360', compact('contact', 'customer360'))->render();
        };

        $hooks->register('contacts.index.row_actions', 'crm.contact_action', $renderAction);
        $hooks->register('contacts.show.header_actions', 'crm.contact_action', $renderAction);
        $hooks->register('contacts.show.after_content', 'crm.customer_360', $renderCustomer360);
    }

    private function registerIntegrationHooks(): void
    {
        /** @var HookManager $hooks */
        $hooks = $this->app->make(HookManager::class);

        $hooks->register('sales.quotations.created', 'crm.timeline.quotation-created', function (array $context): void {
            $quotation = $context['quotation'] ?? null;
            if ($quotation instanceof \App\Modules\Sales\Models\SaleQuotation) {
                app(CrmCustomer360Bridge::class)->handleQuotationCreated($quotation);
            }
        });

        $hooks->register('sales.quotations.converted', 'crm.timeline.quotation-converted', function (array $context): void {
            $quotation = $context['quotation'] ?? null;
            if ($quotation instanceof \App\Modules\Sales\Models\SaleQuotation) {
                app(CrmCustomer360Bridge::class)->handleQuotationConverted($quotation);
            }
        });

        $hooks->register('sales.finalized', 'crm.timeline.sale-finalized', function (array $context): void {
            $sale = $context['sale'] ?? null;
            if ($sale instanceof \App\Modules\Sales\Models\Sale) {
                app(CrmCustomer360Bridge::class)->handleSaleFinalized($sale);
            }
        });

        $hooks->register('sales.voided', 'crm.timeline.sale-voided', function (array $context): void {
            $sale = $context['sale'] ?? null;
            if ($sale instanceof \App\Modules\Sales\Models\Sale) {
                app(CrmCustomer360Bridge::class)->handleSaleVoided($sale);
            }
        });

        $hooks->register('payments.posted', 'crm.timeline.payment-posted', function (array $context): void {
            $payment = $context['payment'] ?? null;
            $payables = $context['payables'] ?? collect();

            if ($payment instanceof \App\Modules\Payments\Models\Payment && $payables instanceof \Illuminate\Support\Collection) {
                app(CrmCustomer360Bridge::class)->handlePaymentPosted($payment, $payables);
            }
        });
    }
}
