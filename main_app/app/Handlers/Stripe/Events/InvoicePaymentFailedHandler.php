<?php

namespace App\Handlers\Stripe\Events;

use App\Contracts\Stripe\StripeEventsHandlerInterface;
use App\DTO\Stripe\StripeEvent;

class InvoicePaymentFailedHandler implements StripeEventsHandlerInterface
{
    public function handle(StripeEvent $event): void
    {
        // TODO: Implement handle() method.
    }

    public function supports(StripeEvent $event): bool
    {
        return StripeEventType::InvoicePaymentFailed->value === $event->getType();
    }
}
