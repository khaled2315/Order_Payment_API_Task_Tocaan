<?php

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;
use App\DTOs\PaymentResult;
use App\Models\Payment;

class StripeGateway implements PaymentGatewayInterface
{
    public function process(Payment $payment): PaymentResult
    {
        $successRate = (float) config('payment.stripe_success_rate', 0.85);
        $random = mt_rand() / mt_getrandmax();
        $success = $random < $successRate;

        if ($success) {
            $transactionId = 'ch_'.strtolower(substr(md5(uniqid()), 0, 24));
            $message = 'Stripe payment processed successfully';
            $gatewayResponse = [
                'success' => true,
                'message' => $message,
                'gateway' => 'stripe',
                'timestamp' => now()->toIso8601String(),
            ];

            return new PaymentResult(
                success: true,
                transactionId: $transactionId,
                message: $message,
                gatewayResponse: $gatewayResponse
            );
        }

        $failureMessages = [
            'Payment declined',
            'Insufficient funds',
            'Card authentication failed',
        ];
        $message = $failureMessages[array_rand($failureMessages)];
        $gatewayResponse = [
            'success' => false,
            'message' => $message,
            'gateway' => 'stripe',
            'timestamp' => now()->toIso8601String(),
        ];

        return new PaymentResult(
            success: false,
            transactionId: null,
            message: $message,
            gatewayResponse: $gatewayResponse
        );
    }

    public function getName(): string
    {
        return 'Stripe';
    }

    public function validate(array $data): bool
    {
        return isset($data['amount']) && $data['amount'] > 0;
    }
}
