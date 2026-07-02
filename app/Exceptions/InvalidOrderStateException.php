<?php

namespace App\Exceptions;

use Exception;

class InvalidOrderStateException extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param  string  $message  The exception message
     * @param  int  $code  The exception code
     * @param  \Throwable|null  $previous  The previous throwable used for exception chaining
     */
    public function __construct(string $message = 'Invalid order state for this operation', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
