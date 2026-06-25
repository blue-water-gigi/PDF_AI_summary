<?php

use App\Models\WebhookEvent;
use App\Repositories\WebhookEventRepository;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('Webhook event repository dont make double and fetch row that was recently created (idempotency)', function () {
    $webhookEventRepository = new WebhookEventRepository;

    $eventModel = $webhookEventRepository->findOrCreateByEventId(
        'stripe',
        1,
        'customer.subscription.created',
        ['test' => 'data']
    );

    expect($eventModel->wasRecentlyCreated)->toBeTrue()
        ->and($eventModel->status)->toBe('processing');

    $fetchAgain = $webhookEventRepository->findOrCreateByEventId(
        'stripe',
        1,
        'customer.subscription.created',
        ['test' => 'data']);

    expect($eventModel->id)->toEqual($fetchAgain->id)
        ->and($fetchAgain->wasRecentlyCreated)->toBeFalse()
        ->and($fetchAgain->status)->toBe('processing');
});

it('throws exception when we try to implicitly double the row', function () {
    $webhookEventRepository = new WebhookEventRepository;

    $eventModel = $webhookEventRepository->findOrCreateByEventId(
        'stripe',
        1,
        'customer.subscription.created',
        ['test' => 'data']
    );

    WebhookEvent::create([
        'event_id' => 1,
        'status' => 'processing',
        'platform' => 'stripe',
        'event_type' => 'customer.subscription.created',
        'payload' => ['test' => 'data'],
    ]);
})->throws(UniqueConstraintViolationException::class);
