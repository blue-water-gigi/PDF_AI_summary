<?php

namespace App\Handlers\Stripe\Events;

use App\Contracts\Stripe\StripeEventsHandlerInterface;
use App\DTO\Stripe\StripeEvent;

class InvoicePaymentSucceededHandler implements StripeEventsHandlerInterface
{
    public function handle(StripeEvent $event): void
    {
        // TODO: Implement handle() method.
    }

    public function supports(StripeEvent $event): bool
    {
        return StripeEventType::InvoicePaymentSucceeded->value === $event->getType();
    }
}
