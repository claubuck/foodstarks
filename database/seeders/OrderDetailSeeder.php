<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class OrderDetailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Order::all()->each(function ($order) {
            $products = $order->restaurant->products()->inRandomOrder()->take(3)->get();

            foreach ($products as $product) {
                \App\Models\OrderDetail::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => rand(1, 3),
                    'unit_price' => $product->price
                ]);
            }

            $order->update(['total_amount' => $order->orderDetails()->sum(DB::raw('quantity * unit_price'))]);
        });
    }
}
