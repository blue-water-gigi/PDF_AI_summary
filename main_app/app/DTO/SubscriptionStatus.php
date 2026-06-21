<?php

namespace App\DTO;

enum SubscriptionStatus: string
{
    case INCOMPLETE = 'incomplete';
    case INCOMPLETE_EXPIRED = 'incomplete_expired';
    case TRIALING = 'trialing';
    case ACTIVE = 'active';
    case PAST_DUE = 'past_due';
    case CANCELLED = 'cancelled';
    case UNPAID = 'unpaid';
    case PAUSED = 'paused';


    /**
     * Resolve stripe status enum case.
     *
     * Reason why we don't use ::tryFrom() directly is that
     * this enum class can expand with various cases.
     * So we don't need to loop over them ALL for just stripe status resolving.
     *
     * @param  string  $status
     * @return self|null
     */
    public static function mapStripeStatus(string $status): ?self
    {
        $status = strtolower($status);

        foreach (self::groupStripeStatus() as $case) {
            if ($case->value === $status) {
                return $case;
            }
        }
        return null;
    }

    protected static function groupStripeStatus(): array
    {
        return [
            self::INCOMPLETE,
            self::INCOMPLETE_EXPIRED,
            self::TRIALING,
            self::ACTIVE,
            self::PAST_DUE,
            self::CANCELLED,
            self::UNPAID,
            self::PAUSED,
        ];
    }
}

