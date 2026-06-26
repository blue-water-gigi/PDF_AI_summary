<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\WebhookHandler;
use App\Events\PaymentFailed;
use App\Events\SubscriptionActivated;
use App\Events\SubscriptionCancelled;
use App\Events\SubscriptionUpdated;
use App\Handlers\HandlerDelegator;
use App\Handlers\StripeWebhookHandler;
use App\Handlers\YoomoneyWebhookHandler;
use App\Listeners\SendActivationNotificationListener;
use App\Listeners\SendCancellationNotificationListener;
use App\Listeners\SendPaymentFailedNotificationListener;
use App\Listeners\SendUpdateNotificationListener;
use App\Services\Payment\PaymentGatewayFactory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Override;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[Override]
    public function register(): void
    {
        // Payment factory
        $this->app->singletonIf(
            PaymentGatewayFactory::class,
            fn($app) => new PaymentGatewayFactory($app)
        );

        // platforms webhook's handler
        $this->app->tag([
            StripeWebhookHandler::class,
            YoomoneyWebhookHandler::class,
        ], WebhookHandler::class);

        $this->app->bind(HandlerDelegator::class, fn(Application $app): HandlerDelegator => new HandlerDelegator(
            $app->tagged(WebhookHandler::class),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(
            SubscriptionActivated::class,
            SendActivationNotificationListener::class
        );

        Event::listen(
            SubscriptionCancelled::class,
            SendCancellationNotificationListener::class
        );

        Event::listen(
            SubscriptionUpdated::class,
            SendUpdateNotificationListener::class
        );

        Event::listen(
            PaymentFailed::class,
            SendPaymentFailedNotificationListener::class
        );
    }
}
