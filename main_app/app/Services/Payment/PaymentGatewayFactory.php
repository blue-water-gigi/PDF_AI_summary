<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use InvalidArgumentException;

class PaymentGatewayFactory
{
    /**
     * Array with supported gateways
     *
     * @var array|string[]
     */
    protected array $gateways = [
        'stripe' => StripeGateway::class,
        'yoomoney' => YoomoneyGateway::class,
    ];

    public function __construct(private readonly Application $app) {}

    /**
     * Resolves gateway from the container
     *
     * @throws BindingResolutionException
     */
    public function resolve(string $gateway): PaymentGatewayInterface
    {
        if (! isset($this->gateways[$gateway])) {
            throw new InvalidArgumentException("Unsupported gateway {$gateway}");
        }

        return $this->app->make($this->gateways[$gateway]);
    }
}
