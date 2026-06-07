<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Exceptions\Payment\SubscriptionException;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

readonly class SubscriptionService
{
    /**
     * Create a new class instance.
     */
    public function __construct(private PaymentGatewayInterface $paymentGateway)
    {
    }

    /**
     * Subscribe user to a certain plan
     *
     * @throws Throwable
     */
    public function subscribe(User $user, Plan $plan): ?string
    {
        try {
            if ($user->hasActiveSub()) {
                throw new SubscriptionException(
                    $this->paymentGateway->getGatewayName(),
                    'User already subscribed.',
                );
            }
            $customerId = $this->paymentGateway->createOrRetrieveCustomer($user);

            $user->updateOrFail([
                ...$this->paymentGateway->setSubscriptionData(
                    customerId: $customerId,
                ),
            ]);
            $user->refresh();

            //todo DB interaction gonna be carried with Webhook
//            $user->updateOrFail([
//                'plan_id' => $plan->id,
//                'pdf_count' => 0,
//                'pdf_count_resets_at' => now()->addMonth(),
//                ...$this->paymentGateway->setSubscriptionData(
//                    $subscriptionId,
//                    $customerId,
//                    now()->addMonth()),
//            ]);
            return $this->paymentGateway->createCheckoutSession($user, $plan);
        } catch (Throwable $th) {
            Log::error('Error creating subscription', [
                'gateway' => $this->paymentGateway->getGatewayName(),
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'error' => $th->getMessage(),
            ]);

            throw $th;
        }
    }

    /**
     * Cancel user's subscription
     *
     * @throws SubscriptionException
     * @throws Throwable
     */
    public function cancel(User $user): void
    {
        try {
            if (!$user->hasActiveSub()) {
                throw new SubscriptionException(
                    $this->paymentGateway->getGatewayName(),
                    'No active subscription found.',
                );
            }
            $subscriptionId = $this->paymentGateway->getSubscriptionId($user);

            $this->paymentGateway->cancelSubscription($subscriptionId);

            //todo when Webhook gonna be implemented - merge this into it
            $user->updateOrFail([
                ...$this->paymentGateway->setSubscriptionData(
                    $subscriptionId,
                    null,
                    now()->endOfMonth() // grace period
                ),
            ]);
        } catch (Throwable $th) {
            Log::error('Error canceling subscription', [
                'gateway' => $this->paymentGateway->getGatewayName(),
                'error' => $th->getMessage(),
                'user_id' => $user->id,
                'subscription_id' => $subscriptionId ?? null,
            ]);

            throw $th;
        }
    }

    /**
     * Change current user's plan
     *
     * @throws SubscriptionException
     * @throws Throwable
     */
    public function changePlan(User $user, Plan $plan): void
    {
        try {
            if (!$user->hasActiveSub()) {
                throw new SubscriptionException(
                    $this->paymentGateway->getGatewayName(),
                    'No active subscription found.',
                );
            }
            $subscriptionId = $this->paymentGateway->getSubscriptionId($user);

            $this->paymentGateway->changePlan($subscriptionId, $plan);

            $user->updateOrFail([
                'plan_id' => $plan->id,
                'pdf_count' => 0,
                'pdf_count_resets_at' => now()->addMonth(),
            ]);
        } catch (Throwable $th) {
            Log::error('Error changing plan', [
                'gateway' => $this->paymentGateway->getGatewayName(),
                'error' => $th->getMessage(),
                'user_id' => $user->id,
                'plan_id' => $plan->id,
            ]);

            throw $th;
        }
    }
}
