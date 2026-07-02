<?php

namespace App\Contracts;

use App\DTOs\PaymentResult;
use App\Models\Payment;

interface PaymentGatewayInterface
{
    /**
     * Process a payment through the gateway.
     */
    public function process(Payment $payment): PaymentResult;

    /**
     * Get the gateway name.
     */
    public function getName(): string;

    /**
     * Validate payment data before processing.
     */
    public function validate(array $data): bool;
}
