<?php

namespace App\Providers;

use App\Contracts\Stripe\StripeEventsHandlerInterface;
use App\Handlers\Stripe\Events\CheckoutSessionCompletedHandler;
use App\Handlers\Stripe\Events\CustomerCreatedHandler;
use App\Handlers\Stripe\Events\CustomerSubscriptionCreatedHandler;
use App\Handlers\Stripe\Events\CustomerSubscriptionDeletedHandler;
use App\Handlers\Stripe\Events\CustomerSubscriptionUpdatedHandler;
use App\Handlers\Stripe\Events\InvoicePaymentFailedHandler;
use App\Handlers\Stripe\Events\InvoicePaymentSucceededHandler;
use App\Handlers\Stripe\Events\PaymentIntentFailedHandler;
use App\Handlers\Stripe\Events\PaymentIntentRequiresActionHandler;
use App\Handlers\Stripe\Events\PaymentIntentSucceededHandler;
use App\Handlers\Stripe\StripeEventRouter;
use App\Repositories\WebhookEventRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Stripe\Stripe;
use Stripe\StripeClient;

class StripeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // client
        $this->app->singletonIf(StripeClient::class, fn() => new StripeClient(
            config('services.stripe.secret'),
        ));

        // event router
        $this->app->tag([
            CheckoutSessionCompletedHandler::class,
            CustomerCreatedHandler::class,
            CustomerSubscriptionCreatedHandler::class,
            CustomerSubscriptionUpdatedHandler::class,
            CustomerSubscriptionDeletedHandler::class,
            InvoicePaymentFailedHandler::class,
            InvoicePaymentSucceededHandler::class,
            PaymentIntentSucceededHandler::class,
            PaymentIntentFailedHandler::class,
            PaymentIntentRequiresActionHandler::class,
        ], StripeEventsHandlerInterface::class);

        $this->app->bind(StripeEventRouter::class, function (Application $app) {
            return new StripeEventRouter(
                $app->tagged(StripeEventsHandlerInterface::class),
                $app->make(WebhookEventRepository::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // stripe api key for global use
        Stripe::setApiKey(config('services.stripe.secret'));
    }
}
