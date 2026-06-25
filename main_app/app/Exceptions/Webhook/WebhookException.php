<?php

declare(strict_types=1);

namespace App\Exceptions\Webhook;

use Exception;
use Throwable;

class WebhookException extends Exception
{
    public function __construct(
        private readonly string $platform,
        private readonly string $group = '',
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getPlatform(): string
    {
        return $this->platform;
    }

    public function getGroup(): string
    {
        return $this->group;
    }
}
