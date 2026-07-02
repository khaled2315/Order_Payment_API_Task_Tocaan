<?php

namespace App\Exceptions;

use Exception;

class UnsupportedPaymentMethodException extends Exception
{
    public function __construct(string $message = 'Unsupported payment method')
    {
        parent::__construct($message);
    }
}
