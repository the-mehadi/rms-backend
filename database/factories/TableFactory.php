<?php

namespace Database\Factories;

use App\Models\Table;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Table>
 */
class TableFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'table_number' => fake()->unique()->numberBetween(1, 100),
            'capacity' => fake()->numberBetween(2, 10),
            'status' => fake()->randomElement(['available', 'occupied']),
        ];
    }
}
