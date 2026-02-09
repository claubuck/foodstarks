<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Table::with('restaurant.users')->get()->each(function ($table) {
            $waiter = $table->restaurant->users()->whereIn('role', ['waiter', 'admin'])->first();
            if (! $waiter) {
                return;
            }
            \App\Models\Order::factory(5)->create([
                'restaurant_id' => $table->restaurant_id,
                'branch_id' => $table->branch_id,
                'table_id' => $table->id,
                'user_id' => $waiter->id,
            ]);
        });
    }
}
