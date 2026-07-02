<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Exceptions\UnsupportedPaymentMethodException;
use App\Services\PaymentGateways\CreditCardGateway;
use App\Services\PaymentGateways\PayPalGateway;
use App\Services\PaymentGateways\StripeGateway;

class PaymentGatewayManager
{
    /**
     * Get gateway instance for the specified payment method.
     *
     * @throws UnsupportedPaymentMethodException
     */
    public function gateway(string $method): PaymentGatewayInterface
    {
        return match ($method) {
            'credit_card' => app(CreditCardGateway::class),
            'paypal' => app(PayPalGateway::class),
            'stripe' => app(StripeGateway::class),
            default => throw new UnsupportedPaymentMethodException(
                "Payment method '{$method}' is not supported"
            ),
        };
    }

    /**
     * Get all available payment methods.
     */
    public function availableMethods(): array
    {
        return ['credit_card', 'paypal', 'stripe'];
    }
}
