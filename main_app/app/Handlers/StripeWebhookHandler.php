<?php

namespace App\Handlers;

use App\Contracts\WebhookHandler;
use App\DTO\Webhook;
use App\Exceptions\Webhook\EventRouterException;
use App\Handlers\Stripe\StripeEventRouter;
use App\Handlers\Stripe\StripeWebhookVerifier;
use Stripe\Exception\SignatureVerificationException;
use Throwable;

class StripeWebhookHandler implements WebhookHandler
{
    private const string SUPPORTED_PLATFORM = 'stripe';

    public function __construct(
        private readonly StripeEventRouter $eventRouter,
        private readonly StripeWebhookVerifier $verifier,
    ) {
    }

    public function supports(Webhook $webhook): bool
    {
        return $webhook->getPlatform() === self::SUPPORTED_PLATFORM;
    }


    /**
     * Handle the stripe webhook depending on the webhook's type.
     *
     * @param  Webhook  $webhook
     * @return void
     * @throws SignatureVerificationException
     * @throws Throwable
     * @throws EventRouterException
     */
    public function handle(Webhook $webhook): void
    {
        $event = $this->verifier->constructEventFromWebhook($webhook);

        $this->eventRouter->route($event);
    }

    // verify logic moved to StripeWebhookVerifier to make testing easier
}
