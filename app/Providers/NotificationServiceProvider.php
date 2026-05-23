<?php

namespace App\Providers;

use App\Support\Notifications\NotificationCenter;
use App\Support\Notifications\NotificationDeliveryDispatcher;
use App\Support\Notifications\NotificationPreferenceService;
use App\Support\Notifications\NotificationQueryService;
use App\Support\Notifications\NotificationRecipientResolver;
use App\Support\Notifications\NotificationTypeRegistry;
use App\Support\Notifications\NotificationUrlBuilder;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NotificationTypeRegistry::class);
        $this->app->singleton(NotificationUrlBuilder::class);
        $this->app->singleton(NotificationPreferenceService::class);
        $this->app->singleton(NotificationRecipientResolver::class);
        $this->app->singleton(NotificationDeliveryDispatcher::class);
        $this->app->singleton(NotificationQueryService::class);
        $this->app->singleton(NotificationCenter::class);
    }
}
