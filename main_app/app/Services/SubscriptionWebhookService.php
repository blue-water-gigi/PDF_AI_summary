<?php

namespace App\Services;

use App\DTO\Subscription;
use App\Models\Plan;
use App\Repositories\PlanRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Throwable;

readonly class SubscriptionWebhookService
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        private UserRepository $userRepository,
        private PlanRepository $planRepository,
    ) {
    }

    /**
     * Activate the subscription within the transaction.
     *
     * @param  Subscription  $subscription
     * @return void
     * @throws Throwable
     */
    public function activate(Subscription $subscription): void
    {
        DB::transaction(callback: function () use ($subscription) {
            $user = $this->userRepository->findByGatewayCustomerId($subscription->gatewayCustomerId);
            $plan = $this->planRepository->findByPlanId($subscription->planId);

            $user->subscription
                ->updateOrCreate(
                    [
                        'user_id' => $user->id,
                    ],
                    [
                        'plan_id' => $plan->id,
                        'gateway' => $subscription->gatewayName,
                        'gateway_customer_id' => $subscription->gatewayCustomerId,
                        'gateway_subscription_id' => $subscription->gatewaySubscriptionId,
                        'status' => $subscription->status,
                        'current_period_end' => $subscription->currentPeriodEnd,
                        'cancelled_at' => $subscription->cancelledAt,
                        'trial_ends_at' => $subscription->trialEndsAt
                    ]);

            $subscription->shouldResetUsage
                ? $user->update([
                'plan_id' => $plan->id,
                'pdf_count' => 0,
                'pdf_count_resets_at' => now()->addMonth()
            ])
                : $user->update([
                'plan_id' => $plan->id,
            ]);
        });
    }

    /**
     * Handle payment failure.
     *
     * @param  Subscription  $subscription
     * @return void
     * @throws Throwable
     */
    public function handlePaymentFailed(Subscription $subscription): void
    {
        /* situation 1: first time subscribing, then we don't need to do anything in service
         because user already have basic subscription data in DB and activate method is a
         transaction so we only need to verify user in controller via Inertia that payment failed
         */

        /* situation 2: was subscribed to plan, but then resubscription failed
           then we need to change status, because many platforms trying to retry payment operation.
        */
        DB::transaction(function () use ($subscription) {
            $user = $this->userRepository->findByGatewayCustomerId($subscription->gatewayCustomerId);

            $user->subscription
                ->update([
                    'status' => $subscription->status,
                ]);
        });
    }

    /**
     * Deactivate the subscription within the transaction.
     *
     * @param  Subscription  $subscription
     * @return void
     * @throws Throwable
     */
    public function deactivate(Subscription $subscription): void
    {
        DB::transaction(function () use ($subscription) {
            $user = $this->userRepository->findByGatewayCustomerId($subscription->gatewayCustomerId);
            $basicPlan = once(fn() => Plan::query()->where(['slug' => 'basic'])->first());

            $user->subscription
                ->update([
                    'plan_id' => $basicPlan->id,
//                    'gateway_subscription_id' => $subscription->gatewaySubscriptionId,
                    'status' => $subscription->status,
                    'current_period_end' => $subscription->currentPeriodEnd,
                    'cancelled_at' => $subscription->cancelledAt,
//                    'trial_ends_at' => $subscription->trialEndsAt,
                ]);

            $user->update([
                'plan_id' => $basicPlan->id,
                'pdf_count_resets_at' => now()->addMonth(),
            ]);
        });
    }

    /**
     * Change user's current plan.
     *
     * @param  Subscription  $subscription
     * @return void
     * @throws Throwable
     */
    public function changePlan(Subscription $subscription): void
    {
        DB::transaction(function () use ($subscription) {
            $user = $this->userRepository->findByGatewayCustomerId($subscription->gatewayCustomerId);
            $plan = $this->planRepository->findByPlanId($subscription->planId);

            $user->subscription
                ->update([
                    'plan_id' => $plan->id,
                    'status' => $subscription->status,
                    'current_period_end' => $subscription->currentPeriodEnd,
                ]);

            $user->update([
                'plan_id' => $plan->id,
                'pdf_count' => 0,
                'pdf_count_resets_at' => now()->addMonth()
            ]);
        });
    }

    /**
     * Handle payment success.
     * Duplicates some props from activate method but has idempotency
     *
     * @param  Subscription  $subscription
     * @return void
     * @throws Throwable
     */
    public function handlePaymentSucceed(Subscription $subscription): void
    {
        DB::transaction(function () use ($subscription) {
            $user = $this->userRepository->findByGatewayCustomerId($subscription->gatewayCustomerId);

            $user->subscription
                ->update([
                    'status' => $subscription->status,
                    'current_period_end' => $subscription->currentPeriodEnd,
                ]);

            $user->update([
                'pdf_count' => 0,
                'pdf_count_resets_at' => $subscription->currentPeriodEnd
            ]);
        });
    }
}
