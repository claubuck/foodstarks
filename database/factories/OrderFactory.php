<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'table_id' => \App\Models\Table::factory(),
            'restaurant_id' => \App\Models\Restaurant::factory(),
            'status' => $this->faker->randomElement(['pending', 'completed', 'cancelled']),
            'total_amount' => $this->faker->randomFloat(2, 10, 1000),
            'created_at' => now(),
            'updated_at' => now(),
            'type' => $this->faker->randomElement(['dine_in', 'takeaway', 'delivery']),
            'payment_method' => $this->faker->randomElement(['cash', 'card', 'transfer']),
        ];
    }
}
