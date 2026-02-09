<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Básico',
                'slug' => 'basico',
                'price_monthly' => 29.99,
                'max_branches' => 1,
                'max_users' => 5,
                'features' => ['1 sucursal', '5 empleados', 'Soporte email'],
                'is_active' => true,
            ],
            [
                'name' => 'Profesional',
                'slug' => 'profesional',
                'price_monthly' => 59.99,
                'max_branches' => 3,
                'max_users' => 15,
                'features' => ['3 sucursales', '15 empleados', 'Soporte prioritario'],
                'is_active' => true,
            ],
            [
                'name' => 'Empresa',
                'slug' => 'empresa',
                'price_monthly' => 99.99,
                'max_branches' => 10,
                'max_users' => 50,
                'features' => ['10 sucursales', '50 empleados', 'Soporte dedicado'],
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
