<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Order::all()->each(function ($order) {
            \App\Models\PaymentMethod::create([
                'order_id' => $order->id,
                'amount' => $order->total_amount,
                'method' => fake()->randomElement(['cash', 'credit_card', 'debit_card']),
                'status' => 'completed'
            ]);
        });
    }
}
