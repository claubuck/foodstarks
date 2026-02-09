<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use App\Models\Reservation;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ReservationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Obtén todos los restaurantes
        $restaurants = Restaurant::all();

        foreach ($restaurants as $restaurant) {
            // Obtén las mesas asociadas al restaurante
            $tables = $restaurant->tables;

            foreach ($tables as $table) {
                // Crea una reserva para cada mesa
                Reservation::create([
                    'restaurant_id' => $restaurant->id,
                    'branch_id' => $table->branch_id,
                    'table_id' => $table->id,
                    'customer_name' => fake()->name(),
                    'customer_phone' => fake()->phoneNumber(),
                    'reservation_time' => now()->addDays(rand(1, 7)), // Reserva en los próximos 7 días
                    'party_size' => rand(2, 6), // Tamaño del grupo
                    'status' => fake()->randomElement(['confirmed', 'pending', 'cancelled']),
                    'notes' => fake()->sentence(),
                ]);
            }
        }
    }
}
