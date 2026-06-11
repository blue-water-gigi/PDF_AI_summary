<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Plan;
use App\Models\User;
use Carbon\CarbonInterface;

class YooMoneyGateway implements PaymentGatewayInterface
{

    public function createOrRetrieveCustomer(User $user): string
    {
        // TODO: Implement createOrRetrieveCustomer() method.
    }

    public function createCheckoutSession(User $user, Plan $plan): ?string
    {
        // TODO: Implement createCheckoutSession() method.
    }

    public function cancelSubscription(string $subscriptionId): void
    {
        // TODO: Implement cancelSubscription() method.
    }

    public function changePlan(string $subscriptionId, Plan $plan): void
    {
        // TODO: Implement changePlan() method.
    }

    public function setSubscriptionData(
        ?string $subscriptionId = null,
        ?string $customerId = null,
        ?CarbonInterface $endsAt = null
    ): array {
        // TODO: Implement setSubscriptionData() method.
    }

    public function getGatewayName(): string
    {
        // TODO: Implement getGatewayName() method.
    }

    public function getSubscriptionId(User $user): string
    {
        // TODO: Implement getSubscriptionId() method.
    }
}
