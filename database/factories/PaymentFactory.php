<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'payment_method' => fake()->randomElement(['credit_card', 'paypal', 'stripe']),
            'status' => 'pending',
            'amount' => fake()->randomFloat(2, 10, 1000),
            'transaction_id' => null,
            'gateway_response' => null,
        ];
    }

    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'successful',
            'transaction_id' => 'TXN-'.time().'-'.strtoupper(substr(md5(uniqid()), 0, 6)),
            'gateway_response' => [
                'success' => true,
                'message' => 'Payment processed successfully',
            ],
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'transaction_id' => null,
            'gateway_response' => [
                'success' => false,
                'message' => 'Payment declined',
            ],
        ]);
    }
}
