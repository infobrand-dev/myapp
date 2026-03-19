<?php

namespace App\Modules\SocialMedia;

use App\Modules\Conversations\Contracts\ConversationOutboundDispatcher;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\SocialMedia\Jobs\SendSocialMessage;
use Illuminate\Support\ServiceProvider;

class SocialMediaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->afterResolving(ConversationOutboundDispatcher::class, function (ConversationOutboundDispatcher $dispatcher): void {
            $dispatcher->register('social_dm', function (ConversationMessage $message): void {
                SendSocialMessage::dispatch($message->id);
            });
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'socialmedia');
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }
}
