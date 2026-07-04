<?php

namespace App\Exceptions\Ai;

use Exception;
use Throwable;

class AiClientException extends Exception
{
    public function __construct(private readonly string $client, string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getClient(): string
    {
        return $this->client;
    }
}
