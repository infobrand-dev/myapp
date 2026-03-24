<?php

namespace Database\Seeders;

use App\Support\PlanLimit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Default public-facing subscription plans.
     *
     * Limits use the PlanLimit constants as keys.
     * A limit of -1 means unlimited.
     * Features is a map of feature-key => bool.
     */
    private array $plans = [
        [
            'code'             => 'free',
            'name'             => 'Free',
            'billing_interval' => 'monthly',
            'is_active'        => true,
            'is_public'        => true,
            'is_system'        => false,
            'sort_order'       => 10,
            'features' => [
                'whatsapp'       => false,
                'email_marketing'=> false,
                'chatbot'        => false,
                'social_media'   => false,
                'reports'        => false,
                'api_access'     => false,
            ],
            'limits' => [
                PlanLimit::COMPANIES           => 1,
                PlanLimit::USERS               => 3,
                PlanLimit::PRODUCTS            => 50,
                PlanLimit::CONTACTS            => 200,
                PlanLimit::WHATSAPP_INSTANCES  => 0,
                PlanLimit::EMAIL_CAMPAIGNS     => 0,
            ],
        ],
        [
            'code'             => 'starter',
            'name'             => 'Starter',
            'billing_interval' => 'monthly',
            'is_active'        => true,
            'is_public'        => true,
            'is_system'        => false,
            'sort_order'       => 20,
            'features' => [
                'whatsapp'       => true,
                'email_marketing'=> false,
                'chatbot'        => false,
                'social_media'   => false,
                'reports'        => true,
                'api_access'     => false,
            ],
            'limits' => [
                PlanLimit::COMPANIES           => 1,
                PlanLimit::USERS               => 10,
                PlanLimit::PRODUCTS            => 500,
                PlanLimit::CONTACTS            => 2000,
                PlanLimit::WHATSAPP_INSTANCES  => 1,
                PlanLimit::EMAIL_CAMPAIGNS     => 5,
            ],
        ],
        [
            'code'             => 'professional',
            'name'             => 'Professional',
            'billing_interval' => 'monthly',
            'is_active'        => true,
            'is_public'        => true,
            'is_system'        => false,
            'sort_order'       => 30,
            'features' => [
                'whatsapp'       => true,
                'email_marketing'=> true,
                'chatbot'        => true,
                'social_media'   => true,
                'reports'        => true,
                'api_access'     => true,
            ],
            'limits' => [
                PlanLimit::COMPANIES           => 3,
                PlanLimit::USERS               => 50,
                PlanLimit::PRODUCTS            => 5000,
                PlanLimit::CONTACTS            => 20000,
                PlanLimit::WHATSAPP_INSTANCES  => 3,
                PlanLimit::EMAIL_CAMPAIGNS     => 50,
            ],
        ],
        [
            'code'             => 'enterprise',
            'name'             => 'Enterprise',
            'billing_interval' => 'monthly',
            'is_active'        => true,
            'is_public'        => true,
            'is_system'        => false,
            'sort_order'       => 40,
            'features' => [
                'whatsapp'       => true,
                'email_marketing'=> true,
                'chatbot'        => true,
                'social_media'   => true,
                'reports'        => true,
                'api_access'     => true,
            ],
            'limits' => [
                PlanLimit::COMPANIES           => -1,
                PlanLimit::USERS               => -1,
                PlanLimit::PRODUCTS            => -1,
                PlanLimit::CONTACTS            => -1,
                PlanLimit::WHATSAPP_INSTANCES  => -1,
                PlanLimit::EMAIL_CAMPAIGNS     => -1,
            ],
        ],
        [
            // Internal plan — assigned to the bootstrap tenant, never shown publicly.
            'code'             => 'internal-unlimited',
            'name'             => 'Internal (Unlimited)',
            'billing_interval' => 'monthly',
            'is_active'        => true,
            'is_public'        => false,
            'is_system'        => true,
            'sort_order'       => 0,
            'features' => [
                'whatsapp'       => true,
                'email_marketing'=> true,
                'chatbot'        => true,
                'social_media'   => true,
                'reports'        => true,
                'api_access'     => true,
            ],
            'limits' => [
                PlanLimit::COMPANIES           => -1,
                PlanLimit::USERS               => -1,
                PlanLimit::PRODUCTS            => -1,
                PlanLimit::CONTACTS            => -1,
                PlanLimit::WHATSAPP_INSTANCES  => -1,
                PlanLimit::EMAIL_CAMPAIGNS     => -1,
            ],
        ],
    ];

    public function run(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('subscription_plans')) {
            return;
        }

        foreach ($this->plans as $plan) {
            DB::table('subscription_plans')->updateOrInsert(
                ['code' => $plan['code']],
                [
                    'name'             => $plan['name'],
                    'billing_interval' => $plan['billing_interval'],
                    'is_active'        => $plan['is_active'],
                    'is_public'        => $plan['is_public'],
                    'is_system'        => $plan['is_system'],
                    'sort_order'       => $plan['sort_order'],
                    'features'         => json_encode($plan['features']),
                    'limits'           => json_encode($plan['limits']),
                    'meta'             => null,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]
            );
        }
    }
}
