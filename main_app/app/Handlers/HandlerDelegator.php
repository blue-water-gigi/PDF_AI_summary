<?php

declare(strict_types=1);

namespace App\Handlers;

use App\Contracts\WebhookHandler;
use App\DTO\Webhook;
use App\Exceptions\Webhook\HandleDelegatorException;

readonly class HandlerDelegator
{
    /**
     * Resolved via tags through service container
     *
     * @param  iterable<WebhookHandler>  $handlers
     */
    public function __construct(private iterable $handlers) {}

    /**
     * Loops through handlers and delegates to the right one for certain Webhook.
     *
     * @throws HandleDelegatorException
     */
    public function delegate(Webhook $webhook): void
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($webhook)) {
                $handler->handle($webhook);

                return;
            }
        }

        throw new HandleDelegatorException(
            $webhook->getPlatform(),
            message: 'No handler found for this webhook.',
            code: 404,
        );
    }
}
