<?php

declare(strict_types=1);

namespace App\Exceptions\Webhook;

use Throwable;

class EventRouterException extends WebhookException
{
    public function __construct(
        private readonly string $eventType,
        string $platform,
        string $group = '',
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($platform, $group, $message, $code, $previous);
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }
}
