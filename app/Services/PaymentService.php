<?php

namespace App\Services;

use App\Exceptions\InvalidOrderStateException;
use App\Exceptions\InvalidPaymentAmountException;
use App\Exceptions\OrderNotFoundException;
use App\Exceptions\UnauthorizedPaymentException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private PaymentGatewayManager $gatewayManager
    ) {}

    /**
     * Process a payment for an order.
     *
     * @param  User  $user  The user making the payment
     * @param  array  $data  Payment data containing:
     *                       - order_id (int): The order ID to process payment for
     *                       - payment_method (string): Payment method identifier
     *                       - amount (float): Payment amount
     * @return Payment The processed payment with transaction details
     *
     * @throws OrderNotFoundException When the order does not exist
     * @throws UnauthorizedPaymentException When user doesn't own the order
     * @throws InvalidOrderStateException When order is not confirmed
     * @throws InvalidPaymentAmountException When payment amount doesn't match order total
     */
    public function processPayment(User $user, array $data): Payment
    {
        $this->validatePaymentRequest($data, $user);

        return DB::transaction(function () use ($data) {
            $payment = Payment::create([
                'order_id' => $data['order_id'],
                'payment_method' => $data['payment_method'],
                'status' => 'pending',
                'amount' => $data['amount'],
            ]);

            $gateway = $this->gatewayManager->gateway($data['payment_method']);
            $result = $gateway->process($payment);

            $payment->update([
                'status' => $result->success ? 'successful' : 'failed',
                'transaction_id' => $result->transactionId,
                'gateway_response' => $result->gatewayResponse,
            ]);

            return $payment->fresh();
        });
    }

    /**
     * Get paginated payments for a user with optional order filter.
     *
     * @param  User  $user  The user whose payments to retrieve
     * @param  int|null  $orderId  Optional order ID to filter payments for a specific order
     * @return LengthAwarePaginator Paginated collection of payments for the user
     */
    public function getPayments(User $user, ?int $orderId = null): LengthAwarePaginator
    {
        $query = Payment::query()
            ->whereHas('order', function (Builder $q) use ($user) {
                $q->where('user_id', $user->id);
            });

        if ($orderId) {
            $query->where('order_id', $orderId);
        }

        return $query->latest()->paginate(15);
    }

    /**
     * Validate payment request data.
     *
     * @param  array  $data  Payment data containing order_id and amount
     * @param  User  $user  The user making the payment
     *
     * @throws OrderNotFoundException When the order does not exist
     * @throws UnauthorizedPaymentException When user doesn't own the order
     * @throws InvalidOrderStateException When order is not confirmed
     * @throws InvalidPaymentAmountException When payment amount doesn't match order total
     */
    public function validatePaymentRequest(array $data, User $user): void
    {
        $order = Order::find($data['order_id']);

        if (! $order) {
            throw new OrderNotFoundException('Order not found');
        }

        if ($order->user_id !== $user->id) {
            throw new UnauthorizedPaymentException('You do not have permission to pay for this order');
        }

        if (! $order->isConfirmed()) {
            throw new InvalidOrderStateException('Payments can only be processed for confirmed orders');
        }

        if (abs($order->total_amount - $data['amount']) > 0.01) {
            throw new InvalidPaymentAmountException('Payment amount must match order total');
        }
    }
}
