<?php

namespace App\Handlers\Stripe;

use App\Contracts\Stripe\StripeEventsHandlerInterface;
use App\DTO\Stripe\StripeEvent;
use App\Exceptions\Webhook\EventRouterException;
use App\Handlers\Stripe\Events\StripeEventType;
use Illuminate\Support\Facades\Log;

readonly class StripeEventRouter
{

    /**
     * @param  iterable<StripeEventsHandlerInterface>  $handlers
     */
    public function __construct(private iterable $handlers)
    {
    }

    /**
     * Routes event to the corresponding handler.
     *
     * @param  StripeEvent  $event
     * @return void
     * @throws EventRouterException
     */
    public function route(StripeEvent $event): void
    {
        $type = StripeEventType::tryFrom($event->getType());

        if (!$type) {
            Log::info("Stripe event type {$event->getType()} not supported");
            return;
        }

        foreach ($this->handlers as $handler) {
            if ($handler->supports($event)) {
                $handler->handle($event);
                return;
            }
        }

        throw new EventRouterException(
            $event->getType(),
            'stripe',
            $type->group(),
            'Error handling event',
            500,
        );
    }
}
