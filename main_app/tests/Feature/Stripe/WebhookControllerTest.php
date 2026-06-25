<?php

use App\DTO\SubscriptionStatus;
use App\DTO\Webhook;
use App\Handlers\Stripe\StripeWebhookVerifier;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\Config;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    $this->seed();
    // Ensure tests use a deterministic webhook secret (matches unit verifier tests)
    $secret = 'whsec_test_secret';
    putenv('STRIPE_WEBHOOK_SECRET='.$secret);
    Config::set('services.stripe.webhook_secret', $secret);
});

/**
 * Helper to create a properly signed Stripe webhook request
 */
function createSignedWebhookRequest(array $payload): array
{
    // If $payload is the Stripe "object" (subscription object) fixture (no top-level id/type),
    // wrap it into a full Stripe event envelope so Stripe\Webhook::constructEvent can parse it.
    if (! isset($payload['id']) || ! isset($payload['type'])) {
        $eventPayload = [
            'id' => 'evt_test_'.uniqid('', true),
            'object' => 'event',
            'type' => 'customer.subscription.created',
            'data' => [
                'object' => $payload['object'] ?? $payload,
            ],
        ];
        $requestBody = json_encode($eventPayload, JSON_THROW_ON_ERROR);
    } else {
        $requestBody = json_encode($payload, JSON_THROW_ON_ERROR);
    }
    $timestamp = (string) time();
    $webhookSecret = config('services.stripe.webhook_secret');

    // Generate Stripe signature exactly as Stripe does
    $signedContent = "{$timestamp}.{$requestBody}";
    $signature = hash_hmac('sha256', $signedContent, (string) $webhookSecret);
    $stripeSignature = "t={$timestamp},v1={$signature}";

    return [
        'body' => $requestBody,
        'signature' => $stripeSignature,
    ];
}

/**
 * Send a signed webhook request to the server
 */
function sendSignedWebhookRequest(object $testCase, string $url, array $payload): mixed
{
    $signed = createSignedWebhookRequest($payload);

    // perform verification to ensure our signing logic produces a valid signature
    $verifier = new StripeWebhookVerifier;
    $webhookDto = new Webhook(
        payload: $payload,
        platform: 'stripe',
        rawBody: $signed['body'],
        headers: ['Stripe-Signature' => $signed['signature']]
    );
    $verifier->constructEventFromWebhook($webhookDto);

    // Use call() to send raw body and then convert base response to TestResponse so assertions work
    $baseResponse = $testCase->call(
        'POST',
        $url,
        [],
        [],
        [],
        ['HTTP_STRIPE_SIGNATURE' => $signed['signature'], 'CONTENT_TYPE' => 'application/json'],
        $signed['body']
    );

    // no debug dumps in normal test runs
    return TestResponse::fromBaseResponse($baseResponse);
}

it('processes stripe event and returns webhook response', function () {
    // Create a user
    User::factory()->create();

    $payload = getFromCustomerSubscriptionCreated();
    $signed = createSignedWebhookRequest($payload);

    // Use withHeaders() to set Stripe-Signature and send raw body
    $response = $this->withHeaders(['Stripe-Signature' => $signed['signature']])
        ->call('POST', '/webhook/stripe', [], [], [], [], $signed['body']);

    // keep as-is; assertions will fail if not successful
    $response->assertSuccessful();
});

it('handles customer.subscription.created event end-to-end', function () {
    // Arrange: Create a user
    $user = User::factory()->create();

    // Get real webhook payload from fixture
    $fixtureData = getFromCustomerSubscriptionCreated();
    $signed = createSignedWebhookRequest($fixtureData);

    // Act: Send the webhook request using helper which signs and posts raw body
    $response = sendSignedWebhookRequest($this, '/webhook/stripe', $fixtureData);

    // Assert: Webhook was processed successfully
    $response->assertSuccessful()
        ->assertJson(['message' => 'Webhook received from stripe']);

    // Verify subscription was created in database
    $subscription = Subscription::where('gateway', 'stripe')
        ->where('gateway_subscription_id', $fixtureData['object']['id'])
        ->first();

    expect($subscription)->not->toBeNull()
        ->and($subscription->gateway_customer_id)->toBe('cus_Ui0rCTaDk3JR7n')
        ->and($subscription->status->value)->toBe('active');

    // Verify webhook event was recorded as processed
    $webhookEvent = WebhookEvent::where('gateway', 'stripe')
        ->where('event_id', $fixtureData['object']['id'])
        ->first();

    expect($webhookEvent)->not->toBeNull()
        ->and($webhookEvent->event_type)->toBe('customer.subscription.created')
        ->and($webhookEvent->status)->toBe('processed');
});

it('returns 200 OK even if subscription already exists (idempotent)', function () {
    // Arrange: Create a user and existing subscription
    $user = User::factory()->create();

    $fixtureData = getFromCustomerSubscriptionCreated();
    $signed = createSignedWebhookRequest($fixtureData);

    // Create existing subscription with same gateway IDs
    Subscription::create([
        'user_id' => $user->id,
        'plan_id' => null,
        'gateway' => 'stripe',
        'gateway_customer_id' => $fixtureData['object']['customer'],
        'gateway_subscription_id' => $fixtureData['object']['id'],
        'status' => SubscriptionStatus::ACTIVE,
        'current_period_end' => now()->addMonth(),
    ]);

    // Act: Send the webhook request using helper which signs and posts raw body
    $response1 = sendSignedWebhookRequest($this, '/webhook/stripe', $fixtureData);
    $response2 = sendSignedWebhookRequest($this, '/webhook/stripe', $fixtureData);

    // Assert: Both responses are successful
    $response1->assertSuccessful();
    $response2->assertSuccessful();

    // Verify only one webhook event exists (idempotent)
    $webhookCount = WebhookEvent::where('gateway', 'stripe')
        ->where('event_id', $fixtureData['object']['id'])
        ->count();

    expect($webhookCount)->toBe(1);

    // Verify subscription still has correct data
    $subscription = Subscription::where('gateway_subscription_id', $fixtureData['object']['id'])->first();
    expect($subscription->user_id)->toBe($user->id);
});

it('returns 400 for invalid webhook signature', function () {
    $fixtureData = getFromCustomerSubscriptionCreated();

    // Act: Send webhook with invalid signature
    $response = $this->postJson('/webhook/stripe', $fixtureData, [
        'Stripe-Signature' => 't=invalid,v1=invalid_signature',
    ]);

    // Assert: Request is rejected
    $response->assertStatus(400)
        ->assertSee('Invalid payload or signature');
});

it('verifies webhook event data is correctly saved', function () {
    // Arrange
    $user = User::factory()->create();

    $fixtureData = getFromCustomerSubscriptionCreated();
    $signed = createSignedWebhookRequest($fixtureData);

    // Act
    $response = $this->withHeaders(['Stripe-Signature' => $signed['signature']])->call('POST', '/webhook/stripe', [],
        [], [], [], $signed['body']);

    // Assert
    $response->assertSuccessful();

    // Verify webhook event contains the full payload
    $webhookEvent = WebhookEvent::where('gateway', 'stripe')
        ->where('event_id', $fixtureData['object']['id'])
        ->first();

    expect($webhookEvent)->not->toBeNull();

    $eventData = json_decode((string) $webhookEvent->payload, true);
    expect($eventData['object']['status'])->toBe('active')
        ->and($eventData['object']['customer'])->toBe('cus_Ui0rCTaDk3JR7n')
        ->and($eventData['object']['items']['data'])->toHaveCount(1);
});
