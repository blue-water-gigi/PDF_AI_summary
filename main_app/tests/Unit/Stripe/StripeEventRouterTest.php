<?php

use App\Contracts\Stripe\StripeEventsHandlerInterface;
use App\DTO\Stripe\StripeEvent;
use App\Handlers\Stripe\Events\StripeEventType;
use App\Handlers\Stripe\StripeEventRouter;

test('StripeEventRouter routes event to the first supporting handler', function () {
    $event = new StripeEvent(
        'event_1',
        StripeEventType::CheckoutSessionCompleted->value,
        [],
        [],
    );

    $mockHandler = $this->createMock(StripeEventsHandlerInterface::class);

    $mockHandler->expects($this->once())
        ->method('supports')
        ->with($event)
        ->willReturn(true);

    $mockHandler->expects($this->once())
        ->method('handle')
        ->with($event);

    $router = new StripeEventRouter([$mockHandler]);

    $router->route($event);
});

