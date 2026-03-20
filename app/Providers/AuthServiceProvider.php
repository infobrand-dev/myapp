<?php

namespace App\Providers;

use App\Models\User;
use App\Modules\Payments\Models\Payment;
use App\Policies\PaymentPolicy;
use App\Support\TenantContext;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Payment::class => PaymentPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::before(function (User $user) {
            $tenantId = TenantContext::resolveIdFromUser($user);

            if ($tenantId === null) {
                return false;
            }

            return $tenantId === TenantContext::currentId() ? null : false;
        });
    }
}
