<?php

namespace App\Modules\Crm;

use App\Modules\Contacts\Models\Contact;
use App\Modules\Crm\Models\CrmLead;
use App\Support\HookManager;
use App\Support\RegistersModuleRoutes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class CrmServiceProvider extends ServiceProvider
{
    use RegistersModuleRoutes;

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerModuleRoutes([__DIR__ . '/routes/web.php']);
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'crm');
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
        $this->registerContactHooks();
        $this->registerDashboardHooks();
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

            $baseQuery = CrmLead::query()
                ->where('tenant_id', \App\Support\TenantContext::currentId())
                ->where('is_archived', false);

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
            if (!Schema::hasTable('crm_leads')) {
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

        $hooks->register('contacts.index.row_actions', 'crm.contact_action', $renderAction);
        $hooks->register('contacts.show.header_actions', 'crm.contact_action', $renderAction);
    }
}
