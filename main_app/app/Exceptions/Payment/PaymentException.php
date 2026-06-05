<?php

declare(strict_types=1);

namespace App\Exceptions\Payment;

use Exception;
use Throwable;

class PaymentException extends Exception
{
    public function __construct(
        private readonly string $gateway,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getGateway(): string
    {
        return $this->gateway;
    }
}
