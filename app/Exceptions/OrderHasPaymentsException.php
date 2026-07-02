<?php

namespace App\Exceptions;

use Exception;

class OrderHasPaymentsException extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param  string  $message  The exception message
     * @param  int  $code  The exception code
     * @param  \Throwable|null  $previous  The previous throwable used for exception chaining
     */
    public function __construct(string $message = 'Cannot delete order with associated payments', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
