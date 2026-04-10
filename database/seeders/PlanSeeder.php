<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'slug'          => 'starter',
                'name'          => 'Starter',
                'description'   => 'For small clinics getting started',
                'price'         => 0,
                'billing_cycle' => 'monthly',
                'trial_days'    => 14,
                'modules'       => [
                    'patients', 'doctors', 'appointments', 'visits', 'billing',
                ],
                'limits'        => [
                    'max_users'    => 3,
                    'max_patients' => 100,
                    'max_doctors'  => 2,
                ],
                'sort_order'    => 1,
            ],
            [
                'slug'          => 'professional',
                'name'          => 'Professional',
                'description'   => 'For growing hospitals',
                'price'         => 49.00,
                'billing_cycle' => 'monthly',
                'trial_days'    => 14,
                'modules'       => [
                    'patients', 'doctors', 'appointments', 'visits', 'billing',
                    'pharmacy', 'laboratory', 'ipd', 'reports', 'rbac',
                ],
                'limits'        => [
                    'max_users'    => 25,
                    'max_patients' => null, // unlimited
                    'max_doctors'  => 10,
                ],
                'sort_order'    => 2,
            ],
            [
                'slug'          => 'enterprise',
                'name'          => 'Enterprise',
                'description'   => 'For hospital networks — everything included',
                'price'         => 149.00,
                'billing_cycle' => 'monthly',
                'trial_days'    => 30,
                'modules'       => [
                    'patients', 'doctors', 'appointments', 'visits', 'billing',
                    'pharmacy', 'laboratory', 'ipd', 'reports', 'rbac',
                    'audit', 'backup',
                ],
                'limits'        => [
                    'max_users'    => null, // unlimited
                    'max_patients' => null,
                    'max_doctors'  => null,
                ],
                'sort_order'    => 3,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan,
            );
        }
    }
}
