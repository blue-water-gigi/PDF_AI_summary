<?php

namespace App\DTO;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;

readonly class Subscription implements Arrayable
{

    /**
     * @param  int  $userId
     * @param  string  $gatewayName
     * @param  string  $gatewayCustomerId
     * @param  string  $gatewaySubscriptionId
     * @param  SubscriptionStatus  $status
     * @param  int|null  $planId
     * @param  Carbon|null  $currentPeriodEnd
     * @param  Carbon|null  $cancelledAt
     * @param  Carbon|null  $trialEndsAt
     * @param  bool|null  $shouldResetUsage
     */
    public function __construct(
        public ?int $userId = null,
        public ?string $gatewayName = null,
        public ?string $gatewayCustomerId = null,
        public ?string $gatewaySubscriptionId = null,
        public ?SubscriptionStatus $status = null,
        public ?int $planId = null,
        public ?Carbon $currentPeriodEnd = null,
        public ?Carbon $cancelledAt = null,
        public ?Carbon $trialEndsAt = null,
        public array|string|null $description = [],

        public bool $isNewSubscription = false,
        public bool $isCancelled = false,
        public bool $isPaymentFailed = false,
        public bool $isPaymentSucceeded = false,
//        public bool $requiresAction = false,
        public bool $isUpdated = false,
    ) {
    }

    public function toArray(): array
    {
        return [
            'gatewayData' => [
                'gatewayName' => $this->gatewayName,
                'gatewayCustomerId' => $this->gatewayCustomerId,
                'gatewaySubscriptionId' => $this->gatewaySubscriptionId,
                'currentPeriodEnd' => $this->currentPeriodEnd,
                'cancelledAt' => $this->cancelledAt,
                'trialEndsAt' => $this->trialEndsAt,
                'description' => $this->description,
                'isNewSubscription' => $this->isNewSubscription,
                'isCancelled' => $this->isCancelled,
                'isPaymentFailed' => $this->isPaymentFailed,
                'isPaymentSucceeded' => $this->isPaymentSucceeded,
//                'requiresAction' => $this->requiresAction,
            ],
            'modelData' => [
                'userId' => $this->userId,
                'planId' => $this->planId,
                'status' => $this->status?->value,
            ]
        ];
    }
}
