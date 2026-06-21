<?php


use App\DTO\Stripe\StripeEvent;
use App\DTO\SubscriptionStatus;
use App\Http\Requests\Subscription\SubscriptionMapper;

uses(\Tests\TestCase::class);

test('SubscriptionMapper::fromCheckoutSessionCompleted extracts the required information from event and forms correct Subscription DTO',
    function () {
        $json = getCheckoutSessionCompletedMock();

        $json['object']['customer'] = 'cus_test';
        $json['object']['subscription'] = 'sub_test';
        $json['object']['metadata']['user_id'] = 1;
        $json['object']['metadata']['plan_id'] = 1;

        $event = new StripeEvent(
            $json['object']['id'],
            'checkout.session.completed',
            $json['object'],
            $json['object']['metadata']
        );

        $subscription = SubscriptionMapper::fromCheckoutSessionCompleted($event);

        expect($subscription)->toBeInstanceOf(App\DTO\Subscription::class)
            ->and($subscription)->toHaveProperties([
                'userId',
                'gatewayName',
                'gatewayCustomerId',
                'gatewaySubscriptionId',
                'status',
                'planId',
                'currentPeriodEnd',
                'cancelledAt',
                'trialEndsAt',
                'shouldResetUsage'
            ])
            ->and($subscription->toArray())->toMatchArray([
                'gatewayData' => [
                    'gatewayName' => 'stripe',
                    'gatewayCustomerId' => $json['object']['customer'],
                    'gatewaySubscriptionId' => $json['object']['subscription'],
                    'currentPeriodEnd' => null,
                    'cancelledAt' => null,
                    'trialEndsAt' => null,
                ],
                'modelData' => [
                    'userId' => $json['object']['metadata']['user_id'],
                    'planId' => $json['object']['metadata']['plan_id'],
                    'status' => SubscriptionStatus::ACTIVE->value,
                    'shouldResetUsage' => true
                ]
            ]);
    });

test('SubscriptionMapper::fromInvoicePaymentSucceeded extracts the required information from event and forms correct Subscription DTO',
    function () {
        $json = getFromInvoicePaymentSucceededMock();

        $json['object']['subscription'] = 'sub_test'; // stripe on default don't involve subscription in invoice object, but in real session it does
        $json['object']['metadata']['user_id'] = 1;
        $json['object']['metadata']['plan_id'] = 1;

        $event = new StripeEvent(
            $json['object']['id'],
            'invoice.payment.succeeded',
            $json['object'],
            $json['object']['metadata']
        );

        $subscription = SubscriptionMapper::fromInvoicePaymentSucceeded($event);

        expect($subscription)->toBeInstanceOf(App\DTO\Subscription::class)
            ->and($subscription)->toHaveProperties([
                'userId',
                'gatewayName',
                'gatewayCustomerId',
                'gatewaySubscriptionId',
                'status',
                'planId',
                'currentPeriodEnd',
                'cancelledAt',
                'trialEndsAt',
                'shouldResetUsage'
            ])
            ->and($subscription->toArray())->toMatchArray([
                'gatewayData' => [
                    'gatewayName' => 'stripe',
                    'gatewayCustomerId' => $json['object']['customer'],
                    'gatewaySubscriptionId' => $json['object']['subscription'],
                    'currentPeriodEnd' => \Illuminate\Support\Carbon::createFromTimestamp($json['object']['lines']['data'][0]['period']['end']),
                    'cancelledAt' => null,
                    'trialEndsAt' => null,
                ],
                'modelData' => [
                    'userId' => $json['object']['metadata']['user_id'],
                    'planId' => $json['object']['metadata']['plan_id'],
                    'status' => SubscriptionStatus::ACTIVE->value,
                    'shouldResetUsage' => true
                ]
            ]);
    });

test('SubscriptionMapper::fromSubscriptionDeleted extracts the required information from event and forms correct Subscription DTO',
    function () {
        $json = getFromCustomerSubscriptionDeleted();

        $json['object']['metadata']['user_id'] = 1;
        $json['object']['metadata']['plan_id'] = 1;

        $event = new StripeEvent(
            $json['object']['id'],
            'invoice.subscription.deleted',
            $json['object'],
            $json['object']['metadata']
        );

        $subscription = SubscriptionMapper::fromSubscriptionDeleted($event);

        expect($subscription)->toBeInstanceOf(App\DTO\Subscription::class)
            ->and($subscription)->toHaveProperties([
                'userId',
                'gatewayName',
                'gatewayCustomerId',
                'gatewaySubscriptionId',
                'status',
                'planId',
                'currentPeriodEnd',
                'cancelledAt',
                'trialEndsAt',
                'shouldResetUsage'
            ])
            ->and($subscription->toArray())->toMatchArray([
                'gatewayData' => [
                    'gatewayName' => 'stripe',
                    'gatewayCustomerId' => $json['object']['customer'],
                    'gatewaySubscriptionId' => $json['object']['id'],
                    'currentPeriodEnd' => null,
                    'cancelledAt' => \Illuminate\Support\Carbon::createFromTimestamp($json['object']['canceled_at']),
                    'trialEndsAt' => null,
                ],
                'modelData' => [
                    'userId' => $json['object']['metadata']['user_id'],
                    'planId' => $json['object']['metadata']['plan_id'],
                    'status' => SubscriptionStatus::CANCELLED->value,
                    'shouldResetUsage' => null
                ]
            ]);
    });
