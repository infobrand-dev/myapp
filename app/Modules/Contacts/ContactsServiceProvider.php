<?php

namespace App\Modules\Contacts;

use App\Modules\Contacts\Models\Contact;
use App\Modules\Contacts\Support\ContactPhoneNormalizer;
use App\Support\HookManager;
use App\Support\RegistersModuleRoutes;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class ContactsServiceProvider extends ServiceProvider
{
    use RegistersModuleRoutes;

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
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
        $this->registerConversationHooks();
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
