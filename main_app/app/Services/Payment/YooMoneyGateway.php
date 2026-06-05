<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Plan;
use App\Models\User;

class YooMoneyGateway implements PaymentGatewayInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function createSubscription(User $user, Plan $plan): string
    {
        // TODO: Implement createSubscription() method.
        return '';
    }

    public function createOrRetrieveCustomer(User $user): string
    {
        // TODO: Implement createOrRetrieveCustomer() method.
        return '';
    }

    public function cancelSubscription(string $subscriptionId): void
    {
        // TODO: Implement cancelSubscription() method.
    }

    public function changePlan(string $subscriptionId, Plan $plan): void
    {
        // TODO: Implement changePlan() method.
    }
}
