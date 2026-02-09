<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Restaurant::with('branches')->get()->each(function ($restaurant) {
            $branch = $restaurant->branches->first();
            if (! $branch) {
                return;
            }
            for ($i = 1; $i <= 10; $i++) {
                \App\Models\Table::create([
                    'restaurant_id' => $restaurant->id,
                    'branch_id' => $branch->id,
                    'table_number' => (string) $i,
                    'capacity' => rand(2, 8),
                    'status' => 'available',
                ]);
            }
        });
    }
}
