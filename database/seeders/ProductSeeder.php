<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\ProductCategory::with('restaurant.branches')->get()->each(function ($category) {
            $branch = $category->restaurant->branches->first();
            if (! $branch) {
                return;
            }
            \App\Models\Product::factory(5)->create([
                'restaurant_id' => $category->restaurant_id,
                'branch_id' => $branch->id,
                'category_id' => $category->id,
            ]);
        });
    }
}
