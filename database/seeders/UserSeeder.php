<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $restaurants = Restaurant::get();

        foreach ($restaurants as $restaurant) {
            \App\Models\User::factory()->create([
                'restaurant_id' => $restaurant->id,
                'role' => 'admin',
                'email' => 'admin@gmail.com',
                'password' => bcrypt('1234'),
            ]);

            \App\Models\User::factory(1)->create([
                'restaurant_id' => $restaurant->id,
                'role' => 'waiter',
                'email' => 'waiter@gmail.com',
                'password' => bcrypt('1234'),
            ]);
        }
    }
}
