<?php

use App\DTO\Stripe\StripeEvent;
use App\DTO\Subscription;
use App\DTO\SubscriptionStatus;
use App\Mappers\SubscriptionMapper;
use Carbon\Carbon;
use Tests\TestCase;

uses(TestCase::class);

test('SubscriptionMapper extracts the required information from customer.subscription events and forms correct Subscription DTO',
    function (
        mixed $json,
        string $type,
        bool $isNewSubscription,
        bool $isCancelled,
        bool $isPaymentFailed,
        bool $isPaymentSucceeded,
        bool $isUpdated,
        SubscriptionStatus $status,
        ?Carbon $currentPeriodEnd,
        ?Carbon $cancelledAt
    ) {

        $json['object']['metadata']['user_id'] = 1;
        $json['object']['metadata']['plan_id'] = 1;

        $event = new StripeEvent(
            $json['object']['id'],
            $type,
            $json['object'],
            $json['object']['metadata']
        );

        $subscription = SubscriptionMapper::fromStripeEvent($event);

        expect($subscription)->toBeInstanceOf(Subscription::class)
            ->and($subscription)->toHaveProperties([
                'gatewayName',
                'status',
                'gatewayCustomerId',
                'gatewaySubscriptionId',
                'currentPeriodEnd',
                'cancelledAt',
                'trialEndsAt',
                'description',
                'isNewSubscription',
                'isCancelled',
                'isPaymentFailed',
                'isPaymentSucceeded',
                'isUpdated',
                'userId',
                'planId',
            ])
            ->and($subscription->toArray())->toMatchArray([
                'gatewayData' => [
                    'gatewayName' => 'stripe',
                    'gatewayCustomerId' => $json['object']['customer'],
                    'gatewaySubscriptionId' => $json['object']['subscription'] ?? $json['object']['id'],
                    'currentPeriodEnd' => $currentPeriodEnd,
                    'cancelledAt' => $cancelledAt,
                    'trialEndsAt' => null,
                    'description' => [],
                    'isNewSubscription' => $isNewSubscription,
                    'isCancelled' => $isCancelled,
                    'isPaymentFailed' => $isPaymentFailed,
                    'isPaymentSucceeded' => $isPaymentSucceeded,
                    'isUpdated' => $isUpdated,
                ],
                'modelData' => [
                    'userId' => $json['object']['metadata']['user_id'],
                    'planId' => $json['object']['metadata']['plan_id'],
                    'status' => $status->value,
                ],
            ]);
    })->with([
        [
            getFromCustomerSubscriptionCreated(), 'customer.subscription.created', true, false, false, false, false,
            SubscriptionStatus::ACTIVE, null, null,
        ],
        [
            getFromCustomerSubscriptionUpdated(), 'customer.subscription.updated', false, false, false, false, true,
            SubscriptionStatus::ACTIVE, Carbon::createFromTimestamp(1784123138), null,
        ],
        [
            getFromCustomerSubscriptionDeleted(), 'customer.subscription.deleted', false, true, false, false, false,
            SubscriptionStatus::CANCELED, null, Carbon::createFromTimestamp(1781456440),
        ],
    ]);

test('SubscriptionMapper extracts the required information from invoice.payment events and forms correct Subscription DTO',
    function (
        mixed $json,
        string $type,
        bool $isNewSubscription,
        bool $isCancelled,
        bool $isPaymentFailed,
        bool $isPaymentSucceeded,
        bool $isUpdated,
        SubscriptionStatus $status,
        ?Carbon $currentPeriodEnd,
        ?Carbon $cancelledAt
    ) {

        $event = new StripeEvent(
            $json['object']['id'],
            $type,
            $json['object'],
            $json['object']['metadata']
        );

        $subscription = SubscriptionMapper::fromStripeEvent($event);

        expect($subscription)->toBeInstanceOf(Subscription::class)
            ->and($subscription)->toHaveProperties([
                'gatewayName',
                'status',
                'gatewayCustomerId',
                'gatewaySubscriptionId',
                'currentPeriodEnd',
                'cancelledAt',
                'trialEndsAt',
                'description',
                'isNewSubscription',
                'isCancelled',
                'isPaymentFailed',
                'isPaymentSucceeded',
                'isUpdated',
                'userId',
                'planId',
            ])
            ->and($subscription->toArray())->toMatchArray([
                'gatewayData' => [
                    'gatewayName' => 'stripe',
                    'gatewayCustomerId' => $json['object']['customer'],
                    'gatewaySubscriptionId' => null,
                    'currentPeriodEnd' => $currentPeriodEnd,
                    'cancelledAt' => $cancelledAt,
                    'trialEndsAt' => null,
                    'description' => [],
                    'isNewSubscription' => $isNewSubscription,
                    'isCancelled' => $isCancelled,
                    'isPaymentFailed' => $isPaymentFailed,
                    'isPaymentSucceeded' => $isPaymentSucceeded,
                    'isUpdated' => $isUpdated,
                ],
                'modelData' => [
                    'userId' => null,
                    'planId' => null,
                    'status' => $status->value,
                ],
            ]);
    })->with([
        [
            getFromInvoicePaymentSucceeded(), 'invoice.payment_succeeded', false, false, false, true, false,
            SubscriptionStatus::ACTIVE, Carbon::createFromTimestamp(1781454271), null,
        ],
        [
            getFromInvoicePaymentFailed(), 'invoice.payment_failed', false, false, true, false, false,
            SubscriptionStatus::PAST_DUE, null, null,
        ],
    ]);
