<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Throwable;

class SubscriptionModelException extends Exception
{
    public function __construct(
        string $gatewayName,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
