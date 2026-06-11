<?php

namespace App\Handlers\Stripe;

use App\DTO\Stripe\StripeEvent;
use App\DTO\Webhook;
use App\Exceptions\Webhook\WebhookVerifierException;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook as StripeWebhook;
use Throwable;
use UnexpectedValueException;

readonly class StripeWebhookVerifier
{
    /**
     * Verify webhook payload and convert to DTO
     *
     * @param  Webhook  $webhook
     * @return StripeEvent
     * @throws SignatureVerificationException
     * @throws UnexpectedValueException
     * @throws Throwable
     */
    public function constructEventFromWebhook(Webhook $webhook): StripeEvent
    {
        try {
            $event = StripeWebhook::constructEvent(
                $webhook->getRawBody(),
                $webhook->getSignature(),
                config('services.stripe.webhook_secret'),
            );

            return new StripeEvent(
                $event->id,
                $event->type,
                $event->data->object ?? [],
                $event->data->object->metadata ?? [],
            );
        } catch (UnexpectedValueException $e) {
            Log::error('Invalid payload. Value does not match with a set of values', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString(),
                'webhook' => $webhook,
            ]);

            throw new WebhookVerifierException(
                $webhook->getPlatform(),
                message: 'Invalid payload. Value does not match with a set of values',
                code: 500,
                previous: $e->getPrevious(),
            );
        } catch (SignatureVerificationException $e) {
            Log::error('Invalid signature. Signature verification for a webhook fails', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString(),
                'webhook' => $webhook,
            ]);

            throw new WebhookVerifierException(
                $webhook->getPlatform(),
                message: 'Invalid signature. Signature verification for a webhook fails',
                code: 500,
                previous: $e->getPrevious(),
            );
        } catch (Throwable $th) {
            Log::error('Error verifying signature.', [
                'error' => $th->getMessage(),
                'line' => $th->getLine(),
                'file' => $th->getFile(),
                'trace' => $th->getTraceAsString(),
                'webhook' => $webhook,
            ]);

            throw new WebhookVerifierException(
                $webhook->getPlatform(),
                message: 'Error verifying signature.',
                code: 500,
                previous: $th->getPrevious(),
            );
        }
    }
}

