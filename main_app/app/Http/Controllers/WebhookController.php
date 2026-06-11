<?php

namespace App\Http\Controllers;

use App\DTO\Webhook;
use App\Handlers\HandlerDelegator;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;
use Symfony\Component\HttpFoundation\Response;
use UnexpectedValueException;

class WebhookController extends Controller
{
    public function __construct(private readonly HandlerDelegator $handler)
    {
    }

    public function __invoke(Request $request, string $platform): Response
    {
        $payload = $request->all();
        $rawBody = $request->getContent();
        $signature = $request->header('Stripe-Signature');
//      $platform = $this->retrievePlatform($request->getRequestUri());

        try {
            $webhook = new Webhook($payload, $platform, $rawBody, $signature);

            $this->handler->delegate($webhook);

            return response()->json([
                'message' => "Webhook received from {$platform}",
            ],
                Response::HTTP_OK
            );
        } catch (UnexpectedValueException $e) {
            return response(
                'Invalid payload',
                Response::HTTP_BAD_REQUEST
            );
        } catch (SignatureVerificationException $e) {
            return response(
                'Invalid signature.',
                Response::HTTP_BAD_REQUEST
            );
        } catch (\Throwable $th) {
            //todo add Error handler

            return response()->json(status: Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

//    /**
//     * Get the platform of received webhook.
//     * To add valid platform set them in payment config file.
//     *
//     * @param  string  $uri
//     * @return string
//     */
//    private function retrievePlatform(string $uri): string
//    {
//        foreach (config('payment.available_gateways') as $gateway) {
//            if (str_contains($uri, $gateway)) {
//                return strtolower($gateway);
//            }
//        }
//        return 'unknown platform';
//    }
}
