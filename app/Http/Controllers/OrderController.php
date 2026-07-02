<?php

namespace App\Http\Controllers;

use App\Http\Requests\Orders\ConfirmOrderRequest;
use App\Http\Requests\Orders\StoreOrderRequest;
use App\Http\Requests\Orders\UpdateOrderRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService
    ) {}

    /**
     * Display a listing of orders.
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $orders = $this->orderService->getOrders(
            $request->user(),
            $status
        );

        return response()->json($orders);
    }

    /**
     * Store a newly created order.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->createOrder(
            $request->user(),
            $request->items
        );

        return response()->json([
            'order' => $order,
        ], 201);
    }

    /**
     * Display the specified order.
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Forbidden',
            ], 403);
        }

        $order->load(['items', 'payments']);

        return response()->json([
            'order' => $order,
        ]);
    }

    /**
     * Update the specified order.
     */
    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        try {
            $order = $this->orderService->updateOrder(
                $order,
                $request->items
            );

            return response()->json([
                'order' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Confirm the specified order.
     */
    public function confirm(ConfirmOrderRequest $request, Order $order): JsonResponse
    {
        try {
            $order = $this->orderService->confirmOrder($order);

            return response()->json([
                'order' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Remove the specified order.
     */
    public function destroy(Request $request, Order $order): JsonResponse
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Forbidden',
            ], 403);
        }

        try {
            $this->orderService->deleteOrder($order);

            return response()->json([
                'message' => 'Order deleted successfully',
            ], 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
