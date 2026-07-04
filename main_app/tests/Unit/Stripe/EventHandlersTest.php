<?php

use App\DTO\Stripe\StripeEvent;
use App\Handlers\Stripe\Events\CheckoutSessionCompletedHandler;
use App\Handlers\Stripe\Events\CustomerSubscriptionCreatedHandler;
use App\Handlers\Stripe\Events\CustomerSubscriptionDeletedHandler;
use App\Handlers\Stripe\Events\CustomerSubscriptionUpdatedHandler;
use App\Handlers\Stripe\Events\InvoicePaymentFailedHandler;
use App\Handlers\Stripe\Events\InvoicePaymentSucceededHandler;
use App\Handlers\Stripe\Events\StripeEventType;
use App\Services\Subscription\SubscriptionWebhookService;

test('Handlers supports their respective events',
    function (string $eventId, string $eventType, array $eventData, array $eventMetadata, array $expectations): void {
        $event = new StripeEvent(
            $eventId,
            $eventType,
            $eventData,
            $eventMetadata
        );

        $service = app()->make(SubscriptionWebhookService::class);

        $handlers = [
            new CheckoutSessionCompletedHandler($service),
            new InvoicePaymentFailedHandler($service),
            new InvoicePaymentSucceededHandler($service),
            new CustomerSubscriptionDeletedHandler($service),
            new CustomerSubscriptionUpdatedHandler($service),
            new CustomerSubscriptionCreatedHandler($service),
        ];

        foreach ($handlers as $index => $handler) {
            expect($handler->supports($event))->toBe($expectations[$index]);
        }
    })->with([
        [
            'checkout.session.completed', StripeEventType::CheckoutSessionCompleted->value, [], [],
            [true, false, false, false, false, false],
        ],
        [
            'invoice.payment_failed', StripeEventType::InvoicePaymentFailed->value, [], [],
            [false, true, false, false, false, false],
        ],
        [
            'invoice.payment_succeeded', StripeEventType::InvoicePaymentSucceeded->value, [], [],
            [false, false, true, false, false, false],
        ],
        [
            'customer.subscription.deleted', StripeEventType::CustomerSubscriptionDeleted->value, [], [],
            [false, false, false, true, false, false],
        ],
        [
            'customer.subscription.updated', StripeEventType::CustomerSubscriptionUpdated->value, [], [],
            [false, false, false, false, true, false],
        ],
        [
            'customer.subscription.created', StripeEventType::CustomerSubscriptionCreated->value, [], [],
            [false, false, false, false, false, true],
        ],
    ]);
