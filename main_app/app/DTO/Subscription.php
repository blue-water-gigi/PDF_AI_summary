<?php

namespace App\DTO;

use Carbon\Carbon;

readonly class Subscription
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
        public int $userId,
        public string $gatewayName,
        public string $gatewayCustomerId,
        public string $gatewaySubscriptionId,
        public SubscriptionStatus $status,
        public ?int $planId = null,
        public ?Carbon $currentPeriodEnd = null,
        public ?Carbon $cancelledAt = null,
        public ?Carbon $trialEndsAt = null,
        public ?bool $shouldResetUsage = null,
    ) {
    }
}
