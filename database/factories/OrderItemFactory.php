<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 5);
        $unitPrice = fake()->randomFloat(2, 5, 200);
        $subtotal = $quantity * $unitPrice;

        return [
            'order_id' => Order::factory(),
            'product_name' => fake()->words(3, true),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $subtotal,
        ];
    }
}
