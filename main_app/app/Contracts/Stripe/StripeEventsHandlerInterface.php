<?php

namespace App\Contracts\Stripe;

use App\DTO\Stripe\StripeEvent;

interface StripeEventsHandlerInterface
{
    public function handle(StripeEvent $event): void;

    public function supports(StripeEvent $event): bool;
}
