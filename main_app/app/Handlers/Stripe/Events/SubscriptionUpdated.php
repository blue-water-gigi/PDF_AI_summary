<?php

namespace App\Handlers\Stripe\Events;

use App\Contracts\Stripe\StripeEventsHandlerInterface;
use App\DTO\Stripe\StripeEvent;
use App\Http\Requests\Subscription\Stripe\SubscriptionMapper;
use App\Services\SubscriptionWebhookService;
use Throwable;

readonly class SubscriptionUpdated implements StripeEventsHandlerInterface
{
    public function __construct(private SubscriptionWebhookService $webhookService)
    {
    }

    /**
     * @param  StripeEvent  $event
     * @return void
     * @throws Throwable
     */
    public function handle(StripeEvent $event): void
    {
        $this->webhookService->changePlan(SubscriptionMapper::fromSubscriptionUpdated($event));
    }

    public function supports(StripeEvent $event): bool
    {
        return StripeEventType::CustomerSubscriptionUpdated->value === $event->getType();
    }
}
