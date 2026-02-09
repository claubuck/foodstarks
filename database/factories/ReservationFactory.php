<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reservation>
 */
class ReservationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_name' => $this->faker->name(),
            'customer_phone' => $this->faker->phoneNumber(),
            'reservation_time' => $this->faker->dateTimeBetween('now', '+1 month'),
            'party_size' => $this->faker->numberBetween(1, 10),
            'status' => $this->faker->randomElement(['pending', 'confirmed', 'canceled']),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
