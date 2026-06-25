<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Plan;
use App\Models\User;
use Carbon\CarbonInterface;

interface PaymentGatewayInterface
{
    public function createOrRetrieveCustomer(User $user): string; // customer ID

    public function createCheckoutSession(User $user, Plan $plan): ?string;

    public function cancelSubscription(string $subscriptionId): void;

    public function changePlan(string $subscriptionId, Plan $plan): void;

    public function setSubscriptionData(
        ?string $subscriptionId = null,
        ?string $customerId = null,
        ?CarbonInterface $endsAt = null
    ): array;

    public function getGatewayName(): string;

    public function getSubscriptionId(User $user): string;
}
