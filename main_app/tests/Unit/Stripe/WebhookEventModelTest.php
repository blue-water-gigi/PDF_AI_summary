<?php

use App\Repositories\WebhookEventRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('WebhookEvent model correctly updates state', function () {
    $repository = app()->make(WebhookEventRepository::class);

    $event = $repository->findOrCreateByEventId(
        'stripe',
        2,
        'customer.subscription.created',
        ['data' => 'test'],
    );

    expect($event->isProcessed())->toBeFalse();

    $event->markAsProcessed();

    expect($event->isProcessed())->toBeTrue()
        ->and($event->status)->toBe('processed')
        ->and($event->processed_at)->not()->toBeNull();

    $event->markAsFailed('error handling event');

    expect($event->isProcessed())->tobeFalse()
        ->and($event->status)->toBe('failed')
        ->and($event->processed_at)->not()->toBeNull()
        ->and($event->attempts)->toBe(1)
        ->and($event->error)->toBe('error handling event');
});
