<?php

namespace Database\Seeders;

use App\Models\MembershipPlan;
use Illuminate\Database\Seeder;

class MembershipPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name'               => 'Visitor Free',
                'slug'               => 'visitor-free',
                'description'        => 'Free visitor account with demo data. Perfect for exploring the platform.',
                'price'              => 0.00,
                'billing_period'     => 'free',
                'features'           => [
                    'Access to risk dashboard (demo data)',
                    'Up to 10 route queries per day',
                    '1 API token',
                    'Basic risk scores for 3 routes',
                    '30-day trial access',
                ],
                'api_token_limit'    => 1,
                'route_query_limit'  => 10,
                'is_active'          => true,
                'sort_order'         => 0,
            ],
            [
                'name'               => 'Basic',
                'slug'               => 'basic-monthly',
                'description'        => 'Essential access for individual analysts and small teams.',
                'price'              => 29.00,
                'billing_period'     => 'monthly',
                'features'           => [
                    'Full risk dashboard access',
                    'Up to 100 route queries per day',
                    '3 API tokens',
                    'Risk scores for up to 20 routes',
                    'Email alerts for high-risk routes',
                    'Standard support',
                ],
                'api_token_limit'    => 3,
                'route_query_limit'  => 100,
                'is_active'          => true,
                'sort_order'         => 1,
            ],
            [
                'name'               => 'Professional',
                'slug'               => 'pro-monthly',
                'description'        => 'Advanced analytics and higher limits for growing operations teams.',
                'price'              => 99.00,
                'billing_period'     => 'monthly',
                'features'           => [
                    'Everything in Basic',
                    'Up to 1,000 route queries per day',
                    '10 API tokens',
                    'Unlimited monitored routes',
                    'Custom scoring profiles',
                    'Priority support',
                    'Data export (CSV)',
                ],
                'api_token_limit'    => 10,
                'route_query_limit'  => 1000,
                'is_active'          => true,
                'sort_order'         => 2,
            ],
            [
                'name'               => 'Enterprise',
                'slug'               => 'enterprise',
                'description'        => 'Full platform access with custom limits and dedicated support.',
                'price'              => 0.00,
                'billing_period'     => 'one_time',
                'features'           => [
                    'Everything in Professional',
                    'Unlimited queries',
                    'Unlimited API tokens',
                    'Custom integrations',
                    'SLA guarantee',
                    'Dedicated account manager',
                    'On-premise deployment option',
                ],
                'api_token_limit'    => 999,
                'route_query_limit'  => 999999,
                'is_active'          => true,
                'sort_order'         => 3,
            ],
        ];

        foreach ($plans as $plan) {
            MembershipPlan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }
    }
}
