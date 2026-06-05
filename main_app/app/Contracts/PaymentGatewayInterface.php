<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Plan;
use App\Models\User;
use Carbon\CarbonInterface;

interface PaymentGatewayInterface
{
    public function createSubscription(User $user, Plan $plan): string; // sub ID

    public function createOrRetrieveCustomer(User $user): string; // customer ID

    public function cancelSubscription(string $subscriptionId): void;

    public function changePlan(string $subscriptionId, Plan $plan): void;

    public function setSubscriptionData(?string $subscriptionId, ?string $customerId, CarbonInterface $endsAt): iterable;

    public function getGatewayName(): string;

    public function getSubscriptionId(User $user): string;

    public function createCheckoutSession(User $user, Plan $plan): ?string;
}
