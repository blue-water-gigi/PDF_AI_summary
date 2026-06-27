<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTO\Webhook;
use App\Exceptions\Webhook\EventRouterException;
use App\Exceptions\Webhook\HandleDelegatorException;
use App\Exceptions\Webhook\WebhookVerifierException;
use App\Handlers\HandlerDelegator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class WebhookController extends Controller
{
    public function __construct(private readonly HandlerDelegator $handler) {}

    /**
     * Handle incoming webhook from a payment gateway.
     * Any unhandled exception (e.g. EventRouterException — handler not found
     * for a known event type) is intentionally caught and answered with 200,
     * so Stripe does not spam retries for what is likely a code-level issue
     * rather than a transient one.
     */
    public function __invoke(Request $request, string $platform): Response
    {
        $webhook = new Webhook(
            $request->all(),
            $platform,
            $request->getContent(),
            [
                ...$request->headers->all(),
                'Stripe-Signature' => $request->header('Stripe-Signature') ?? $request->server('HTTP_STRIPE_SIGNATURE'),
            ]
        );

        try {
            $this->handler->delegate($webhook);

            return response()->json([
                'message' => "Webhook received from {$platform}",
            ], Response::HTTP_OK);
        } catch (WebhookVerifierException) {
            return response(
                'Invalid payload or signature.',
                Response::HTTP_BAD_REQUEST
            );
        } catch (HandleDelegatorException) {
            return response(
                'Unknown webhook platform.',
                Response::HTTP_NOT_FOUND
            );
        } catch (EventRouterException $e) {
            // Known event type but no handler configured — don't retry
            Log::warning('Event type not handled.', [
                'platform' => $platform,
                'event_type' => $e->getMessage(),
            ]);

            return response()->json(status: Response::HTTP_OK);
        } catch (Throwable $th) {
            Log::error('Unhandled webhook processing error.', [
                'platform' => $platform,
                'error' => $th->getMessage(),
                'code' => $th->getCode(),
                'trace' => $th->getTraceAsString(),
            ]);

            // Return 500 so Stripe retries transient failures
            return response(
                'Internal server error',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
