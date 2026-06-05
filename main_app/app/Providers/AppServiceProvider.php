<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Payment\PaymentGatewayFactory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Override;
use Stripe\Stripe;
use Stripe\StripeClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[Override]
    public function register(): void
    {
        // client
        $this->app->singletonIf(StripeClient::class, fn() => new StripeClient(
            config('services.stripe.secret'),
        ));

        // Payment factory
        $this->app->singletonIf(PaymentGatewayFactory::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // stripe api key for global use
        Stripe::setApiKey(config('services.stripe.secret'));
    }
}
