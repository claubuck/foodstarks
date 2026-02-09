<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = ['Entradas', 'Platos Principales', 'Bebidas', 'Postres', 'Especiales'];

        if (\App\Models\Restaurant::count() === 0) {
            $this->command->warn('No restaurants found. Skipping ProductCategorySeeder.');
            return;
        }


        $restaurants = \App\Models\Restaurant::all();

        if ($restaurants->isEmpty()) {
            $this->command->warn('No restaurants found. Skipping ProductCategorySeeder.');
            return;
        }

        $restaurants->each(function ($restaurant) use ($categories) {
            foreach ($categories as $category) {
                \App\Models\ProductCategory::create([
                    'restaurant_id' => $restaurant->id,
                    'name' => $category,
                    'description' => fake()->sentence()
                ]);
            }
        });
    }
}
