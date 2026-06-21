<?php

namespace App\Services;

use App\DTO\Subscription;
use App\Exceptions\SubscriptionModelException;
use App\Models\Subscription as SubscriptionModel;
use App\Models\User;
use App\Repositories\PlanRepository;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
     * @param  Subscription  $dto
     * @return void
     * @throws ModelNotFoundException
     * @throws SubscriptionModelException
     * @throws Throwable
     */
    public function syncWithStripe(Subscription $dto): void
    {
        DB::transaction(function () use ($dto) {
            $user = $this->userRepository->findByGatewayCustomerId($dto->gatewayCustomerId);

            $subscription = $this->getSubscriptionOrFail(
                $user,
                $dto->gatewayName,
            );

            if ($dto->isCancelled) {
                $subscription->update([
                    'status' => $dto->status,
                    'cancelled_at' => $dto->cancelledAt,
                ]);

                return;
            }

            if ($dto->isPaymentFailed) {
                $subscription->update([
                    'status' => $dto->status,
                ]);

                return;
            }

            if ($dto->isPaymentSucceeded) {
                $subscription->update([
                    'status' => $dto->status,
                    'current_period_end' => $dto->currentPeriodEnd,
                ]);

                $user->update([
                    'pdf_count' => 0,
                    'pdf_count_resets_at' => $dto->currentPeriodEnd,
                ]);

                return;
            }

            if ($dto->isUpdated) {
                $subscription->update([
                    'plan_id' => $dto->planId,
                    'gateway' => $dto->gatewayName,
                    'gateway_subscription_id' => $dto->gatewaySubscriptionId,
                    'current_period_end' => $dto->currentPeriodEnd,
                    'status' => $dto->status,
                ]);
            }

            if ($dto->isNewSubscription) {
                $subscription->update([
                    'user_id' => $dto->userId,
                    'plan_id' => $dto->planId,
                    'gateway' => $dto->gatewayName,
                    'gateway_customer_id' => $dto->gatewayCustomerId,
                    'gateway_subscription_id' => $dto->gatewaySubscriptionId,
                    'status' => $dto->status,
                ]);
            }
        });
    }

//    public function syncWithYoomoney(Subscription $dto): void
//    {
//    }

    /**
     * @param  User  $user
     * @param  string  $gatewayName
     * @return SubscriptionModel
     * @throws SubscriptionModelException
     */
    protected function getSubscriptionOrFail(User $user, string $gatewayName): SubscriptionModel
    {
        return $user->subscription ?? throw new SubscriptionModelException(
            $gatewayName,
            "No subscription record found for user {$user->id}"
        );
    }
}
