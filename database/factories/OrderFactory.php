<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Table;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'table_id' => Table::factory(),
            'user_id' => User::factory(),
            'status' => fake()->randomElement(['pending', 'preparing', 'ready', 'served', 'cancelled']),
            'special_note' => fake()->optional(0.3)->sentence(), // 30% chance of note
        ];
    }
}
