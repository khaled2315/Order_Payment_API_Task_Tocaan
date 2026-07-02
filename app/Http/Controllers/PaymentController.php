<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payments\StorePaymentRequest;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    /**
     * Display a listing of payments.
     */
    public function index(Request $request): JsonResponse
    {
        $orderId = $request->query('order_id');
        $payments = $this->paymentService->getPayments(
            $request->user(),
            $orderId
        );

        return response()->json($payments);
    }

    /**
     * Process a new payment.
     */
    public function store(StorePaymentRequest $request): JsonResponse
    {
        try {
            $payment = $this->paymentService->processPayment(
                $request->user(),
                $request->validated()
            );

            return response()->json([
                'payment' => $payment,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Display the specified payment.
     */
    public function show(Request $request, Payment $payment): JsonResponse
    {
        $payment->load('order');

        if ($payment->order->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Forbidden',
            ], 403);
        }

        return response()->json([
            'payment' => $payment,
        ]);
    }
}
