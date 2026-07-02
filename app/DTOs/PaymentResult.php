<?php

namespace App\DTOs;

class PaymentResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $transactionId,
        public readonly string $message,
        public readonly array $gatewayResponse
    ) {}

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'transaction_id' => $this->transactionId,
            'message' => $this->message,
            'gateway_response' => $this->gatewayResponse,
        ];
    }
}
