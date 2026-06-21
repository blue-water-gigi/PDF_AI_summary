<?php

namespace App\Handlers\Stripe\Events;

use App\Contracts\Stripe\StripeEventsHandlerInterface;
use App\DTO\Stripe\StripeEvent;
use App\Http\Requests\Subscription\SubscriptionMapper;
use App\Services\SubscriptionWebhookService;
use Throwable;

readonly class CheckoutSessionCompletedHandler implements StripeEventsHandlerInterface
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
        $this->webhookService->syncWithStripe(SubscriptionMapper::fromStripeEvent($event));
    }

    public function supports(StripeEvent $event): bool
    {
        return StripeEventType::CheckoutSessionCompleted->value === $event->getType();
    }
}
