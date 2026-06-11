<?php

it('processes stripe checkout session and returns webhook response', function () {
    $payload = getStripePayloadExample();

    $response = $this->postJson('/webhook/stripe', $payload);

    $response->assertSuccessful();
});

it('processes stripe checkout session and returns valid webhook response data', function () {
    $payload = getStripePayloadExample();

    $response = $this->postJson('/webhook/stripe', $payload);

    $response->assertJson([
        'message' => 'Webhook received from stripe',
    ]);
});
