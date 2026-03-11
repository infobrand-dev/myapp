<?php

namespace App\Modules\WhatsAppApi;

use App\Modules\WhatsAppApi\Http\Controllers\ContactActionController;
use App\Support\HookManager;
use App\Modules\WhatsAppApi\Console\Commands\CheckWhatsAppInstances;
use App\Modules\WhatsAppApi\Console\Commands\DispatchScheduledWABlasts;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class WhatsAppApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bindings or singletons can be registered here later.
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'whatsappapi');
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        $this->registerContactHooks();

        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            CheckWhatsAppInstances::class,
            DispatchScheduledWABlasts::class,
        ]);

        $this->app->booted(function (): void {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('whatsapp:check-instances')->everyTenMinutes();
            $schedule->command('whatsapp:dispatch-scheduled-blasts')->everyMinute();
        });
    }

    private function registerContactHooks(): void
    {
        if (!class_exists(\App\Modules\Contacts\Models\Contact::class)) {
            return;
        }

        /** @var HookManager $hooks */
        $hooks = $this->app->make(HookManager::class);

        $hooks->register('contacts.index.row_actions', 'whatsapp_api.contact_button', function (array $context): string {
            $contact = $context['contact'] ?? null;
            if (!$contact) {
                return '';
            }

            return view('whatsappapi::contact-actions.button', compact('contact'))->render();
        });

        $hooks->register('contacts.show.header_actions', 'whatsapp_api.contact_button', function (array $context): string {
            $contact = $context['contact'] ?? null;
            if (!$contact) {
                return '';
            }

            return view('whatsappapi::contact-actions.button', compact('contact'))->render();
        });

        $modalRenderer = function (): string {
            $data = ContactActionController::modalData(auth()->user());
            return view('whatsappapi::contact-actions.modal', $data)->render();
        };

        $hooks->register('contacts.index.after_content', 'whatsapp_api.contact_modal', fn () => $modalRenderer());
        $hooks->register('contacts.show.after_content', 'whatsapp_api.contact_modal', fn () => $modalRenderer());
    }
}
