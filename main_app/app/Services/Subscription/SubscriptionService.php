<?php

declare(strict_types=1);

namespace App\Services\Subscription;

use App\Contracts\PaymentGatewayInterface;
use App\DTO\SubscriptionStatus;
use App\Exceptions\Payment\SubscriptionException;
use App\Models\Plan;
use App\Models\Subscription as SubscriptionModel;
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
     * @throws SubscriptionException
     */
    public function subscribe(User $user, Plan $plan): ?string
    {

        if ($user->hasActiveSub()) {
            throw new SubscriptionException(
                $this->paymentGateway->getGatewayName(),
                'User already subscribed.',
            );
        }

        try {
            $customerId = $this->paymentGateway->createOrRetrieveCustomer($user);

            SubscriptionModel::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'gateway' => $this->paymentGateway->getGatewayName(),
                ],
                [
                    'plan_id' => $plan->id,
                    ...$this->paymentGateway->setSubscriptionData(
                        subscriptionId: 'checkout_pending_' . $user->id,
                        customerId: $customerId,
                    ),
                    'status' => SubscriptionStatus::INCOMPLETE,
                ]
            );

            $user->load('subscription');

            return $this->paymentGateway->createCheckoutSession($user, $plan);
        } catch (Throwable $th) {
            Log::error('Error creating subscription', [
                'gateway' => $this->paymentGateway->getGatewayName(),
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'error' => $th->getMessage(),
            ]);

            throw new SubscriptionException(
                $this->paymentGateway->getGatewayName(),
                'Subscription creation failed.',
                previous: $th,
            );
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
            $user->loadMissing('subscription');

            if (!$user->hasActiveSub()) {
                throw new SubscriptionException(
                    $this->paymentGateway->getGatewayName(),
                    'No active subscription found.',
                );
            }
            $subscriptionId = $this->paymentGateway->getSubscriptionId($user);

            $this->paymentGateway->cancelSubscription($subscriptionId, $user);
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
    public function changePlan(User $user, Plan $newPlan): void
    {
        try {
            $user->loadMissing('subscription');

            if (!$user->hasActiveSub()) {
                throw new SubscriptionException(
                    $this->paymentGateway->getGatewayName(),
                    'No active subscription found.',
                );
            }
            $subscriptionId = $this->paymentGateway->getSubscriptionId($user);

            $this->paymentGateway->changePlan($subscriptionId, $newPlan, $user);
        } catch (Throwable $th) {
            Log::error('Error changing plan', [
                'gateway' => $this->paymentGateway->getGatewayName(),
                'error' => $th->getMessage(),
                'user_id' => $user->id,
                'plan_id' => $newPlan->id,
            ]);

            throw $th;
        }
    }
}
