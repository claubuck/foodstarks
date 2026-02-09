<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Restaurant>
 */
class RestaurantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'address' => $this->faker->address(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'logo_url' => $this->faker->imageUrl(640, 480, 'business', true),
            'opening_hours' => json_encode([
                'Monday' => ['09:00', '17:00'],
                'Tuesday' => ['09:00', '17:00'],
                'Wednesday' => ['09:00', '17:00'],
                'Thursday' => ['09:00', '17:00'],
                'Friday' => ['09:00', '17:00'],
                'Saturday' => ['10:00', '16:00'],
                'Sunday' => null,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
