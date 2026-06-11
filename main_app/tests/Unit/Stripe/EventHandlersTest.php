<?php

use App\DTO\Stripe\StripeEvent;
use App\Handlers\Stripe\Events\CheckoutSessionCompletedHandler;
use App\Handlers\Stripe\Events\InvoicePaymentFailedHandler;
use App\Handlers\Stripe\Events\InvoicePaymentSucceededHandler;
use App\Handlers\Stripe\Events\StripeEventType;
use App\Handlers\Stripe\Events\SubscriptionDeletedHandler;
use App\Handlers\Stripe\Events\SubscriptionUpdated;

test('Handlers supports their respective events',
    function (string $eventId, string $eventType, array $eventData, array $eventMetadata, array $expectations): void {
        $event = new StripeEvent(
            $eventId,
            $eventType,
            $eventData,
            $eventMetadata
        );

        $handlers = [
            $firstHandler = new CheckoutSessionCompletedHandler(),
            $secondHandler = new InvoicePaymentFailedHandler(),
            $thirdHandler = new InvoicePaymentSucceededHandler(),
            $fourthHandler = new SubscriptionDeletedHandler(),
            $fifthHandler = new SubscriptionUpdated(),
        ];

        foreach ($handlers as $index => $handler) {
            expect($handler->supports($event))->toBe($expectations[$index]);
            continue;
        }
    })->with([
    ['event_1', StripeEventType::CheckoutSessionCompleted->value, [], [], [true, false, false, false, false]],
    ['event_2', StripeEventType::InvoicePaymentFailed->value, [], [], [false, true, false, false, false]],
    ['event_3', StripeEventType::InvoicePaymentSucceeded->value, [], [], [false, false, true, false, false]],
    ['event_4', StripeEventType::CustomerSubscriptionDeleted->value, [], [], [false, false, false, true, false]],
    ['event_5', StripeEventType::CustomerSubscriptionUpdated->value, [], [], [false, false, false, false, true]],
]);
