<?php

use App\DTO\Stripe\StripeEvent;
use App\DTO\Webhook;
use App\Handlers\Stripe\Events\StripeEventType;
use App\Handlers\Stripe\StripeEventRouter;
use App\Handlers\Stripe\StripeWebhookVerifier;
use App\Handlers\StripeWebhookHandler;

test('StripeWebhookHandler verifies and handles webhook', function () {
    $event = new StripeEvent(
        'event_1',
        StripeEventType::CheckoutSessionCompleted->value,
        [],
        []
    );

    $verifier = $this->createMock(StripeWebhookVerifier::class);
    $eventRouter = $this->createMock(StripeEventRouter::class);

    $verifier->expects($this->once())
        ->method('constructEventFromWebhook')
        ->willReturn($event);

    $eventRouter->expects($this->once())
        ->method('route')
        ->with($event);

    $handler = new StripeWebhookHandler(
        $eventRouter,
        $verifier,
    );

    $webhook = new Webhook(['data' => 'test'], 'stripe', 'body', ['sig']);

    $handler->handle($webhook);
});
