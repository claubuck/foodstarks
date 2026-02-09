<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RestaurantSeeder extends Seeder
{
    public function run(): void
    {
        $restaurant = Restaurant::factory(1)->create()->first();
        Branch::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Casa matriz',
            'address' => $restaurant->address,
            'phone' => $restaurant->phone,
            'is_active' => true,
        ]);
        $plan = SubscriptionPlan::where('is_active', true)->first();
        if ($plan) {
            Subscription::create([
                'restaurant_id' => $restaurant->id,
                'plan_id' => $plan->id,
                'status' => 'trial',
                'current_period_start' => now(),
                'current_period_end' => now()->addDays(14),
            ]);
        }
    }
}
