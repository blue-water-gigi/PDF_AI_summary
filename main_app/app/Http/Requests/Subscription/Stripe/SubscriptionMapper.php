<?php

namespace App\Http\Requests\Subscription\Stripe;

use App\DTO\Stripe\StripeEvent;
use App\DTO\Subscription;
use App\DTO\SubscriptionStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SubscriptionMapper
{

    /**
     * @param  StripeEvent  $event
     * @return Subscription
     */
    public static function fromCheckoutSessionCompleted(StripeEvent $event): Subscription
    {
        $data = $event->getData();
        $metadata = $event->getMetadata();

        $validator = Validator::make($data, [
            'customer' => ['required'],
            'subscription' => ['required'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return new Subscription(
            userId: $metadata['user_id'] ?? null,
            gatewayName: 'stripe',
            gatewayCustomerId: $data['customer'],
            gatewaySubscriptionId: $data['subscription'],
            status: SubscriptionStatus::ACTIVE,
            planId: $metadata['plan_id'] ?? null,
            shouldResetUsage: true
        );
    }

    public static function fromInvoicePaymentSucceeded(StripeEvent $event): Subscription
    {
        $data = $event->getData();
        $metadata = $event->getMetadata();

        $validator = Validator::make($data, [
            'customer' => ['required'],
            'subscription' => ['required'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return new Subscription(
            userId: $metadata['user_id'] ?? null,
            gatewayName: 'stripe',
            gatewayCustomerId: $data['customer'],
            gatewaySubscriptionId: $data['subscription'],
            status: SubscriptionStatus::ACTIVE,
            planId: $metadata['plan_id'] ?? null,
            currentPeriodEnd: Carbon::createFromTimestamp($data['lines']['data'][0]['period']['end']),
            shouldResetUsage: true
        );
    }

    public static function fromSubscriptionDeleted(StripeEvent $event): Subscription
    {
        $data = $event->getData();
        $metadata = $event->getMetadata();

        $validator = Validator::make($data, [
            'customer' => ['required'],
            'id' => ['required'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return new Subscription(
            userId: $metadata['user_id'] ?? null,
            gatewayName: 'stripe',
            gatewayCustomerId: $data['customer'],
            gatewaySubscriptionId: $data['id'],
            status: SubscriptionStatus::CANCELLED,
            planId: $metadata['plan_id'] ?? null,
            currentPeriodEnd: Carbon::createFromTimestamp($data['current_period_end']),
            cancelledAt: Carbon::createFromTimestamp($data['ended_at']),
        );
    }

    public static function fromInvoicePaymentFailed(StripeEvent $event): Subscription
    {
        $data = $event->getData();
        $metadata = $event->getMetadata();

        $validator = Validator::make($data, [
            'customer' => ['required'],
            'subscription' => ['required'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return new Subscription(
            userId: $metadata['user_id'] ?? null,
            gatewayName: 'stripe',
            gatewayCustomerId: $data['customer'],
            gatewaySubscriptionId: $data['subscription'],
            status: SubscriptionStatus::PAST_DUE,
            planId: $metadata['plan_id'] ?? null,
        );
    }

    public static function fromSubscriptionUpdated(StripeEvent $event): Subscription
    {
        $data = $event->getData();
        $metadata = $event->getMetadata();

        $validator = Validator::make($data, [
            'customer' => ['required'],
            'subscription' => ['required'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return new Subscription(
            userId: $metadata['user_id'] ?? null,
            gatewayName: 'stripe',
            gatewayCustomerId: $data['customer'],
            gatewaySubscriptionId: $data['items']['data'][0]['subscription'],
            status: SubscriptionStatus::ACTIVE,
            planId: $metadata['plan_id'] ?? null,
            currentPeriodEnd: Carbon::createFromTimestamp($data['items']['data'][0]['current_period_end']),
        );
    }
}
