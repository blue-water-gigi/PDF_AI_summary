<?php

namespace App\Handlers\Stripe;

use App\Contracts\Stripe\StripeEventsHandlerInterface;
use App\DTO\Stripe\StripeEvent;
use App\Exceptions\Webhook\EventRouterException;
use App\Handlers\Stripe\Events\StripeEventType;
use App\Repositories\WebhookEventRepository;
use Illuminate\Support\Facades\Log;

readonly class StripeEventRouter
{

    /**
     * @param  iterable<StripeEventsHandlerInterface>  $handlers
     */
    public function __construct(
        private iterable $handlers,
        private WebhookEventRepository $webhookEventRepository,
    ) {
    }

    /**
     * Routes event to the corresponding handler.
     * Idempotent
     *
     * @param  StripeEvent  $event
     * @return void
     * @throws EventRouterException
     */
    public function route(StripeEvent $event): void
    {
        $type = $this->getType($event);

        if (!$type) {
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
                'status' => $webhookEvent->status
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

            throw new EventRouterException(
                $event->getType(),
                'stripe',
                $type->group(),
                'No handler found for event type.',
                500,
            );
        } catch (\Throwable $th) {
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
            );
        }
    }

    private function getType(StripeEvent $event): ?StripeEventType
    {
        $type = StripeEventType::tryFrom($event->getType());

        if (!$type) {
            Log::info("Stripe event type {$event->getType()} not supported");
            return null;
        }

        return $type;
    }
}
