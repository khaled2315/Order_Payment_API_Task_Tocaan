<?php

namespace App\Services;

use App\Exceptions\InvalidOrderStateException;
use App\Exceptions\OrderHasPaymentsException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderService
{
    /**
     * Create a new order with items.
     *
     * @param  User  $user  The user creating the order
     * @param  array  $items  Array of order items, each item must contain:
     *                        - product_name (string): Name of the product
     *                        - quantity (int|float): Quantity ordered
     *                        - unit_price (float): Price per unit
     * @return Order The created order with loaded items
     *
     * @throws InvalidArgumentException When items array is invalid or missing required fields
     */
    public function createOrder(User $user, array $items): Order
    {
        $this->validateItems($items);

        return DB::transaction(function () use ($user, $items) {
            $total = $this->calculateTotal($items);

            $order = Order::create([
                'user_id' => $user->id,
                'status' => 'pending',
                'total_amount' => $total,
            ]);

            foreach ($items as $item) {
                $subtotal = $item['quantity'] * $item['unit_price'];
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $subtotal,
                ]);
            }

            return $order->load('items');
        });
    }

    /**
     * Update an existing order with new items.
     *
     * @param  Order  $order  The order to update
     * @param  array  $items  Array of order items, each item must contain:
     *                        - product_name (string): Name of the product
     *                        - quantity (int|float): Quantity ordered
     *                        - unit_price (float): Price per unit
     * @return Order The updated order with loaded items
     *
     * @throws InvalidOrderStateException When order is not in pending status
     * @throws InvalidArgumentException When items array is invalid or missing required fields
     */
    public function updateOrder(Order $order, array $items): Order
    {
        if (! $order->isPending()) {
            throw new InvalidOrderStateException('Only pending orders can be updated');
        }

        $this->validateItems($items);

        return DB::transaction(function () use ($order, $items) {
            $order->items()->delete();

            $total = $this->calculateTotal($items);
            $order->update(['total_amount' => $total]);

            foreach ($items as $item) {
                $subtotal = $item['quantity'] * $item['unit_price'];
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $subtotal,
                ]);
            }

            return $order->load('items');
        });
    }

    /**
     * Confirm an order (change status from pending to confirmed).
     *
     * @param  Order  $order  The order to confirm
     * @return Order The confirmed order
     *
     * @throws InvalidOrderStateException When order is not in pending status
     */
    public function confirmOrder(Order $order): Order
    {
        if (! $order->isPending()) {
            throw new InvalidOrderStateException('Only pending orders can be confirmed');
        }

        $order->update(['status' => 'confirmed']);

        return $order;
    }

    /**
     * Delete an order.
     *
     * @param  Order  $order  The order to delete
     *
     * @throws OrderHasPaymentsException When order has associated payments
     */
    public function deleteOrder(Order $order): void
    {
        if ($order->hasPayments()) {
            throw new OrderHasPaymentsException('Cannot delete order with associated payments');
        }

        $order->delete();
    }

    /**
     * Get paginated orders for a user with optional status filter.
     *
     * @param  User  $user  The user whose orders to retrieve
     * @param  string|null  $status  Optional status filter (e.g., 'pending', 'confirmed', 'completed')
     * @return LengthAwarePaginator Paginated collection of orders with loaded items
     */
    public function getOrders(User $user, ?string $status = null): LengthAwarePaginator
    {
        $query = $user->orders()->with('items');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->latest()->paginate(15);
    }

    /**
     * Calculate total amount from order items.
     *
     * @param  array  $items  Array of items, each containing 'quantity' and 'unit_price' keys
     * @return float Total amount rounded to 2 decimal places
     */
    public function calculateTotal(array $items): float
    {
        $total = 0;

        foreach ($items as $item) {
            $total += $item['quantity'] * $item['unit_price'];
        }

        return round($total, 2);
    }

    /**
     * Validate the structure and content of order items array.
     *
     * @param  array  $items  Array of items to validate
     *
     * @throws InvalidArgumentException When items array is empty or has invalid structure
     */
    private function validateItems(array $items): void
    {
        if (empty($items)) {
            throw new InvalidArgumentException('Order must contain at least one item');
        }

        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                throw new InvalidArgumentException("Item at index {$index} must be an array");
            }

            if (! isset($item['product_name']) || ! is_string($item['product_name']) || trim($item['product_name']) === '') {
                throw new InvalidArgumentException("Item at index {$index} must have a valid 'product_name' string");
            }

            if (! isset($item['quantity']) || (! is_int($item['quantity']) && ! is_float($item['quantity'])) || $item['quantity'] <= 0) {
                throw new InvalidArgumentException("Item at index {$index} must have a valid 'quantity' greater than 0");
            }

            if (! isset($item['unit_price']) || (! is_int($item['unit_price']) && ! is_float($item['unit_price'])) || $item['unit_price'] < 0) {
                throw new InvalidArgumentException("Item at index {$index} must have a valid 'unit_price' greater than or equal to 0");
            }
        }
    }
}
