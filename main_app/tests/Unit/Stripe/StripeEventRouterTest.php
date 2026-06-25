<?php

use App\Contracts\Stripe\StripeEventsHandlerInterface;
use App\Exceptions\Webhook\EventRouterException;
use App\Models\WebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('handles the supported type known event, marks it as "processed" in DB webhook_events table', function () {
    $event = makeMockStripeEvent('customer.subscription.created');

    $handler = $this->mock(StripeEventsHandlerInterface::class);

    $handler->shouldReceive('supports')
        ->withAnyArgs()
        ->andReturn(true);
    $handler->shouldReceive('handle')
        ->withAnyArgs()
        ->andReturnNull();

    makeRouterWithMockedHandler($handler)->route($event);

    $webhookEvent = WebhookEvent::where('event_id', 'test_123')->firstOrFail();

    expect($webhookEvent->status)->toBe('processed')
        ->and($webhookEvent->processed_at)->not()->toBeNull();
});

it('handles the supported type known event only once', function () {
    $event = makeMockStripeEvent('customer.subscription.created');

    $handler = $this->mock(StripeEventsHandlerInterface::class);

    $handler->shouldReceive('supports')->andReturn(true);
    $handler->shouldReceive('handle')->once();

    $router = makeRouterWithMockedHandler($handler);

    $router->route($event);
    $router->route($event);
    $router->route($event);
    $router->route($event);
    $router->route($event);

    $webhookEvent = WebhookEvent::where('event_id', 'test_123')->count();

    expect($webhookEvent)->toBe(1);
});

it('handles the supported type unknown event and throws an exception, marks it as "failed" in DB webhook_events table',
    function () {
        $event = makeMockStripeEvent('customer.subscription.trial_will_end');

        $handler = $this->mock(StripeEventsHandlerInterface::class);

        $handler->shouldReceive('supports')->andReturn(false);
        $handler->shouldReceive('handle')->never();

        $router = makeRouterWithMockedHandler($handler);

        $router->route($event);

        $webhookEvent = WebhookEvent::where('event_id', 'test_123')->firstOrFail();

        expect($webhookEvent->exists())->toBeTrue()
            ->and($webhookEvent->status)->toBe('processed')
            ->and($webhookEvent->processed_at)->not()->toBeNull();
    })->note('we silently ignore events with types present in enum, but dont have handlers');

it('handles the unsupported type unknown event and method ends with no errors, event dont exists in DB', function () {
    $event = makeMockStripeEvent('charge.failed');

    $handler = $this->mock(StripeEventsHandlerInterface::class);

    $handler->shouldReceive('supports')->andReturn(false);
    $handler->shouldReceive('handle')->never();

    $router = makeRouterWithMockedHandler($handler);

    $router->route($event);

    $webhookEvent = WebhookEvent::where('event_id', 'test_123')->exists();

    expect($webhookEvent)->toBeFalse();
});

it('throws an exception when any error occurs, marks it as "failed" in DB webhook_events table', function () {
    $event = makeMockStripeEvent('checkout.session.completed');

    $handler = $this->mock(StripeEventsHandlerInterface::class);

    $handler->shouldReceive('supports')->andReturn(true);
    $handler->shouldReceive('handle')->andThrow(new RuntimeException('Something went wrong'));

    $router = makeRouterWithMockedHandler($handler);
    $router->route($event);
})
    ->throws(EventRouterException::class)
    ->after(function () {
        $webhookEvent = WebhookEvent::where('event_id', 'test_123')->firstOrFail();

        expect($webhookEvent->status)->toBe('failed')
            ->and($webhookEvent->processed_at)->not()->toBeNull()
            ->and($webhookEvent->error)->not()->toBeNull();
    });
