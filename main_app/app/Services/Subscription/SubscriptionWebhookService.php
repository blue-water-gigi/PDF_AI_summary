<?php

declare(strict_types=1);

namespace App\Services\Subscription;

use App\DTO\Subscription;
use App\Events\SubscriptionActivated;
use App\Events\SubscriptionCancelled;
use App\Events\SubscriptionUpdated;
use App\Exceptions\SubscriptionModelException;
use App\Models\Subscription as SubscriptionModel;
use App\Models\User;
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
    )
    {
    }

    /**
     * @throws ModelNotFoundException
     * @throws SubscriptionModelException
     * @throws Throwable
     */
    public function syncWithStripe(Subscription $dto): void
    {
        DB::transaction(function () use ($dto) {
            // For new subscriptions, use userId from DTO; otherwise find by gateway_customer_id
            $user = $dto->userId
                ? $this->userRepository->findById($dto->userId)
                : $this->userRepository->findByGatewayCustomerId($dto?->gatewayCustomerId);

            // For new subscriptions, create directly without requiring existing subscription
            if ($dto->isNewSubscription) {
                $sub = SubscriptionModel::query()->updateOrCreate(
                    [
                        'gateway_customer_id' => $dto->gatewayCustomerId,
                        'gateway' => $dto->gatewayName,
                    ],
                    [
                        'user_id' => $dto->userId,
                        'plan_id' => $dto->planId,
                        'gateway_subscription_id' => $dto->gatewaySubscriptionId,
                        'current_period_end' => $dto->currentPeriodEnd,
                        'status' => $dto->status,
                    ]);

                SubscriptionActivated::dispatch($user, $sub);
                return;
            }

            // For existing subscriptions, fetch the subscription record
            $subscription = $this->getSubscriptionForPayment(
                $user,
                $dto,
            );

            if ($dto->isCancelled) {
                $subscription->update([
                    'status' => $dto->status,
                    'cancelled_at' => $dto->cancelledAt,
                ]);

                SubscriptionCancelled::dispatch($user, $subscription);
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
                    'gateway_subscription_id' => $dto->gatewaySubscriptionId,
                    'status' => $dto->status,
                    'current_period_end' => $dto->currentPeriodEnd,
                ]);

                $user->update([
                    'plan_id' => $subscription->plan_id,
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

                $user->update([
                    'plan_id' => $subscription->plan_id,
                    'pdf_count' => 0,
                    'pdf_count_resets_at' => $dto->currentPeriodEnd,
                ]);

                SubscriptionUpdated::dispatch($user, $subscription);
            }
        });
    }

    public function syncWithYoomoney(Subscription $dto): void
    {
    }

    /**
     * @throws SubscriptionModelException
     */
    private function getSubscriptionOrFail(User $user, string $gatewayName): SubscriptionModel
    {
        return $user->subscription ?? throw new SubscriptionModelException(
            $gatewayName,
            "No subscription record found for user {$user->id}"
        );
    }

    /**
     * @throws SubscriptionModelException
     */
    protected function getSubscriptionForPayment(User $user, Subscription $dto): SubscriptionModel
    {
        return SubscriptionModel::query()
            ->where('user_id', $user->id)
            ->where('gateway', $dto->gatewayName)
            ->when($dto->gatewaySubscriptionId, function ($query) use ($dto) {
                $query->where('gateway_subscription_id', $dto->gatewaySubscriptionId);
            })
            ->first() ?? $this->getSubscriptionOrFail($user, $dto->gatewayName);
    }
}
