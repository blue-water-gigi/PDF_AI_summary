<?php

declare(strict_types=1);

namespace App\Handlers\Stripe\Events;

use App\Contracts\Stripe\StripeEventsHandlerInterface;
use App\DTO\Stripe\StripeEvent;
use App\Mappers\SubscriptionMapper;
use App\Services\SubscriptionWebhookService;
use Throwable;

readonly class CustomerSubscriptionDeletedHandler implements StripeEventsHandlerInterface
{
    public function __construct(private SubscriptionWebhookService $webhookService) {}

    /**
     * @throws Throwable
     */
    public function handle(StripeEvent $event): void
    {
        $this->webhookService->syncWithStripe(SubscriptionMapper::fromStripeEvent($event));
    }

    public function supports(StripeEvent $event): bool
    {
        return StripeEventType::CustomerSubscriptionDeleted->value === $event->getType();
    }
}
