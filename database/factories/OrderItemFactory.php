<?php

namespace Database\Factories;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $menuItem = MenuItem::factory()->create();

        return [
            'order_id' => Order::factory(),
            'menu_item_id' => $menuItem->id,
            'quantity' => fake()->numberBetween(1, 5),
            'price' => $menuItem->price, // Snapshot price
        ];
    }
}
