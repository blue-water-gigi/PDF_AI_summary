<?php

declare(strict_types=1);

namespace App\Handlers\Stripe;

use App\Contracts\Stripe\StripeEventsHandlerInterface;
use App\DTO\Stripe\StripeEvent;
use App\Exceptions\Webhook\EventRouterException;
use App\Handlers\Stripe\Events\StripeEventType;
use App\Repositories\WebhookEventRepository;
use Illuminate\Support\Facades\Log;
use Throwable;

readonly class StripeEventRouter
{
    /**
     * @param  iterable<StripeEventsHandlerInterface>  $handlers
     */
    public function __construct(
        private iterable $handlers,
        private WebhookEventRepository $webhookEventRepository,
    ) {}

    /**
     * Routes event to the corresponding handler.
     * Idempotent
     *
     * @throws EventRouterException
     */
    public function route(StripeEvent $event): void
    {
        $type = $this->getType($event);

        if (! $type instanceof StripeEventType) {
            return;
        }

        $webhookEvent = $this->webhookEventRepository->findOrCreateByEventId(
            'stripe',
            $event->getObjectId(),
            $type->value,
            $event->getData()
        );

        if ($webhookEvent->wasRecentlyCreated === false) {
            Log::info('Stripe event already processed (idempotent duplicate)', [
                'event_id' => $event->getObjectId(),
                'status' => $webhookEvent->status,
            ]);

            return;
        }

        try {
            foreach ($this->handlers as $handler) {
                if ($handler->supports($event)) {
                    $handler->handle($event);
                    $webhookEvent->markAsProcessed();

                    return;
                }
            }

            // have the type in enum but don't have handler then silently go on
            Log::warning('Handler not found', [
                'event_id' => $event->getObjectId(),
                'event_type' => $event->getType(),
            ]);
            $webhookEvent->markAsProcessed();

        } catch (Throwable $th) {
            Log::error('Stripe event processing error.'.$th->getMessage(), [
                'webhookEvent' => $webhookEvent,
            ]);

            $webhookEvent->markAsFailed($th->getMessage());

            throw new EventRouterException(
                $event->getType(),
                'stripe',
                $type->group(),
                'Error handling or processing the event: '.$th->getMessage(),
                500,
                $th
            );
        }
    }

    private function getType(StripeEvent $event): ?StripeEventType
    {
        $type = StripeEventType::tryFrom($event->getType());

        if (! $type) {
            Log::info("Stripe event type {$event->getType()} not supported");

            return null;
        }

        return $type;
    }
}
