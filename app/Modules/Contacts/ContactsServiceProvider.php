<?php

namespace App\Modules\Contacts;

use App\Modules\Contacts\Models\Contact;
use App\Modules\Contacts\Support\ContactPhoneNormalizer;
use App\Support\HookManager;
use App\Support\PlanFeature;
use App\Support\RegistersModuleRoutes;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class ContactsServiceProvider extends ServiceProvider
{
    use RegistersModuleRoutes;

    public const PERMISSIONS = [
        'contacts.view',
        'contacts.create',
        'contacts.update',
        'contacts.delete',
        'contacts.import',
        'contacts.merge',
    ];

    public const DEFAULT_ROLE_PERMISSIONS = [
        'Super-admin' => self::PERMISSIONS,
        'Admin' => self::PERMISSIONS,
        'Customer Service' => [
            'contacts.view',
            'contacts.create',
            'contacts.update',
        ],
        'Sales' => [
            'contacts.view',
            'contacts.create',
            'contacts.update',
        ],
    ];

    public const PLAN_LIMIT_MODELS = [
        \App\Support\PlanLimit::CONTACTS => [
            'table' => 'contacts',
            'model' => \App\Modules\Contacts\Models\Contact::class,
        ],
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerModuleRoutes([__DIR__ . '/routes/web.php']);
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'contacts');
        $this->loadTranslationsFrom(__DIR__ . '/resources/lang', 'contacts');
        $this->loadMigrationsFrom(\App\Support\ModulePath::migrationDirectory(__DIR__) ?? (__DIR__ . '/Database/Migrations'));
        $this->ensurePermissions();
        $this->registerConversationHooks();
        $this->registerDashboardHooks();
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

        $hooks->register('dashboard.overview.cards', 'contacts.dashboard.card', function (): string {
            if (!Schema::hasTable('contacts')) {
                return '';
            }

            $tenantId = TenantContext::currentId();
            $plans = app(\App\Support\TenantPlanManager::class);

            if (!$plans->hasFeature(PlanFeature::CRM, $tenantId) && !$plans->hasFeature(PlanFeature::COMMERCE, $tenantId)) {
                return '';
            }

            $total = Contact::query()->where('tenant_id', $tenantId)->count();
            $newThisMonth = Contact::query()
                ->where('tenant_id', $tenantId)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();

            $limit = $plans->limit(\App\Support\PlanLimit::CONTACTS, $tenantId);

            return view('contacts::dashboard.card', compact('total', 'newThisMonth', 'limit'))->render();
        });
    }

    private function registerConversationHooks(): void
    {
        /** @var HookManager $hooks */
        $hooks = $this->app->make(HookManager::class);

        $hooks->register('conversations.show.detail_rows', 'contacts.crm_panel', function (array $context): string {
            if (!Schema::hasTable('contacts')) {
                return '';
            }

            $conversation = $context['conversation'] ?? null;
            if (!$conversation || empty($conversation->contact_external_id)) {
                return '';
            }

            $contact = $this->findRelatedContact($conversation->contact_external_id);

            return view('contacts::conversations.detail-rows', [
                'conversation' => $conversation,
                'relatedContact' => $contact,
                'canReply' => (bool) ($context['canReply'] ?? false),
            ])->render();
        });
    }

    private function findRelatedContact(?string $contactExternalId): ?Contact
    {
        $phone = ContactPhoneNormalizer::normalize((string) $contactExternalId);
        if (!$phone) {
            return null;
        }

        return Contact::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where(function ($query) use ($phone): void {
                $query->where('mobile', $phone)
                    ->orWhere('phone', $phone);
            })
            ->orderByRaw('CASE WHEN mobile = ? THEN 0 ELSE 1 END', [$phone])
            ->orderBy('name')
            ->first();
    }
}
