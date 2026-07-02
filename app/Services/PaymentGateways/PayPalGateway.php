<?php

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;
use App\DTOs\PaymentResult;
use App\Models\Payment;

class PayPalGateway implements PaymentGatewayInterface
{
    public function process(Payment $payment): PaymentResult
    {
        $successRate = (float) config('payment.paypal_success_rate', 0.9);
        $random = mt_rand() / mt_getrandmax();
        $success = $random < $successRate;

        if ($success) {
            $transactionId = 'PAYPAL-'.time().'-'.strtoupper(substr(md5(uniqid()), 0, 8));
            $message = 'PayPal payment processed successfully';
            $gatewayResponse = [
                'success' => true,
                'message' => $message,
                'gateway' => 'paypal',
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
            'PayPal account suspended',
            'Payment cancelled by user',
            'Insufficient funds in PayPal account',
        ];
        $message = $failureMessages[array_rand($failureMessages)];
        $gatewayResponse = [
            'success' => false,
            'message' => $message,
            'gateway' => 'paypal',
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
        return 'PayPal';
    }

    public function validate(array $data): bool
    {
        return isset($data['amount']) && $data['amount'] > 0;
    }
}
