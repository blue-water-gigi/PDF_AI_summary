<?php

use App\DTO\Webhook;
use App\Handlers\Stripe\StripeEventRouter;
use App\Handlers\Stripe\StripeWebhookVerifier;
use App\Handlers\StripeWebhookHandler;
use App\Handlers\YoomoneyWebhookHandler;

test('Stripe handler supports only stripe platform', function (string $platform, bool $expected) {
    $webhook = new Webhook(['data' => 'test data'], $platform, 'body', 'signature');

    $eventRouter = $this->createMock(StripeEventRouter::class);
    $verifier = $this->createMock(StripeWebhookVerifier::class);

    expect(new StripeWebhookHandler($eventRouter, $verifier)->supports($webhook))->toBe($expected);
})->with([
    ['stripe', true],
    ['yoomoney', false],
    ['random', false]
]);

test('Yoomoney handler supports only yoomoney platform', function (string $platform, bool $expected) {
    $webhook = new Webhook(['data' => 'test data'], $platform, 'body', 'signature');

    expect(new YoomoneyWebhookHandler()->supports($webhook))->toBe($expected);
})->with([
    ['stripe', false],
    ['yoomoney', true],
    ['random', false]
]);


