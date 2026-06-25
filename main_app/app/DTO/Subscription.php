<?php

declare(strict_types=1);

namespace App\DTO;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;

readonly class Subscription implements Arrayable
{
    /**
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
    ) {}

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
                'isUpdated' => $this->isUpdated,
                //                'requiresAction' => $this->requiresAction,
            ],
            'modelData' => [
                'userId' => $this->userId,
                'planId' => $this->planId,
                'status' => $this->status?->value,
            ],
        ];
    }
}
