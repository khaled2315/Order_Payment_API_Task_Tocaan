<?php

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;
use App\DTOs\PaymentResult;
use App\Models\Payment;

class CreditCardGateway implements PaymentGatewayInterface
{
    public function process(Payment $payment): PaymentResult
    {
        $successRate = (float) config('payment.credit_card_success_rate', 0.8);
        $random = mt_rand() / mt_getrandmax();
        $success = $random < $successRate;

        if ($success) {
            $transactionId = 'TXN-'.time().'-'.strtoupper(substr(md5(uniqid()), 0, 6));
            $message = 'Payment processed successfully';
            $gatewayResponse = [
                'success' => true,
                'message' => $message,
                'gateway' => 'credit_card',
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
            'Insufficient funds',
            'Card declined',
            'Invalid card number',
            'Card expired',
        ];
        $message = $failureMessages[array_rand($failureMessages)];
        $gatewayResponse = [
            'success' => false,
            'message' => $message,
            'gateway' => 'credit_card',
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
        return 'Credit Card';
    }

    public function validate(array $data): bool
    {
        return isset($data['amount']) && $data['amount'] > 0;
    }
}
