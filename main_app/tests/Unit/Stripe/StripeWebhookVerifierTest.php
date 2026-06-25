<?php

use App\DTO\Stripe\StripeEvent;
use App\DTO\Webhook;
use App\Exceptions\Webhook\WebhookVerifierException;
use App\Handlers\Stripe\StripeWebhookVerifier;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

uses(TestCase::class);

it('Throws Exception when Stripe signature is invalid', function () {
    // 1. Force the exact secret to be used
    $secret = 'whsec_test_secret';
    putenv('STRIPE_WEBHOOK_SECRET='.$secret);   // override any real env
    Config::set('services.stripe.webhook_secret', $secret);

    // 2. Build a valid Stripe event payload
    $eventData = [
        'id' => 'evt_test_123',
        'object' => 'event',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_test_456',
                'object' => 'payment_intent',
                'metadata' => ['order_id' => '987'],
            ],
        ],
    ];
    $rawBody = json_encode($eventData, JSON_THROW_ON_ERROR);

    // 3. Manually generate the signature
    $timestamp = time();
    $signedPayload = $timestamp.'.'.$rawBody;
    $signature = hash_hmac('sha256', $signedPayload, $secret);
    $stripeSignatureHeader = "t={$timestamp},v1={$signature}";

    // 4. Create the Webhook DTO – header key MUST be lowercase
    $webhook = new Webhook(
        payload: json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR),
        platform: 'stripe',
        rawBody: $rawBody,
        headers: [
            'Stripe-Signature' => $stripeSignatureHeader.'trash',
        ],
    );

    // 5. Verify
    $verifier = new StripeWebhookVerifier;
    $event = $verifier->constructEventFromWebhook($webhook);

})->throws(WebhookVerifierException::class);

it('Throws Exception when Stripe payload is damaged or not valid', function () {
    // 1. Force the exact secret to be used
    $secret = 'whsec_test_secret';
    putenv('STRIPE_WEBHOOK_SECRET='.$secret);   // override any real env
    Config::set('services.stripe.webhook_secret', $secret);

    // 2. Build a invalid Stripe event payload
    $eventData = [
        'id' => 'evt_test_123',
        'object' => 'event',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_test_456',
                'object' => 'payment_intent',
                'metadata' => ['order_id' => '987'],
            ],
        ],
    ];
    $rawBody = '{"id":"evt_test_123"}';

    // 3. Manually generate the signature
    $timestamp = time();
    $signedPayload = $timestamp.'.'.$rawBody;
    $signature = hash_hmac('sha256', $signedPayload, $secret);
    $stripeSignatureHeader = "t={$timestamp},v1={$signature}";

    // 4. Create the Webhook DTO – header key MUST be lowercase
    $webhook = new Webhook(
        payload: [],
        platform: 'stripe',
        rawBody: $rawBody,
        headers: [
            'Stripe-Signature' => $stripeSignatureHeader,
        ],
    );

    // 5. Verify
    $verifier = new StripeWebhookVerifier;
    $event = $verifier->constructEventFromWebhook($webhook);

})->throws(WebhookVerifierException::class);

it('Creates stripe event DTO when signature is valid', function () {

    // 1. Force the exact secret to be used
    $secret = 'whsec_test_secret';
    putenv('STRIPE_WEBHOOK_SECRET='.$secret);   // override any real env
    Config::set('services.stripe.webhook_secret', $secret);

    // 2. Build a valid Stripe event payload
    $eventData = [
        'id' => 'evt_test_123',
        'object' => 'event',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_test_456',
                'object' => 'payment_intent',
                'metadata' => ['order_id' => '987'],
            ],
        ],
    ];
    $rawBody = json_encode($eventData, JSON_THROW_ON_ERROR);

    // 3. Manually generate the signature
    $timestamp = time();
    $signedPayload = $timestamp.'.'.$rawBody;
    $signature = hash_hmac('sha256', $signedPayload, $secret);
    $stripeSignatureHeader = "t={$timestamp},v1={$signature}";

    // 4. Create the Webhook DTO – header key MUST be lowercase
    $webhook = new Webhook(
        payload: json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR),
        platform: 'stripe',
        rawBody: $rawBody,
        headers: [
            'Stripe-Signature' => $stripeSignatureHeader,
        ],
    );

    // 5. Verify
    $verifier = new StripeWebhookVerifier;
    $event = $verifier->constructEventFromWebhook($webhook);

    expect($event)->tobeInstanceOf(StripeEvent::class)
        ->and($event->getObjectId())->toBe('evt_test_123')
        ->and($event->getType())->toBe('payment_intent.succeeded')
        ->and($event->getData())->toBe($eventData['data']['object'])
        ->and($event->getMetadata())->toBe($eventData['data']['object']['metadata']);
});
