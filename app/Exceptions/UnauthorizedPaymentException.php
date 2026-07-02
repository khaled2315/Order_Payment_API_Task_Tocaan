<?php

namespace App\Exceptions;

use Exception;

class UnauthorizedPaymentException extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param  string  $message  The exception message
     * @param  int  $code  The exception code
     * @param  \Throwable|null  $previous  The previous throwable used for exception chaining
     */
    public function __construct(string $message = 'You do not have permission to perform this payment operation', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
