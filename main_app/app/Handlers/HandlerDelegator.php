<?php

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
    public function __construct(private iterable $handlers)
    {
    }

    /**
     * Loop through handlers and choose the right one for certain webhook.
     *
     * @param  Webhook  $webhook
     * @return void
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
            code: 500,
        );
    }
}
