<?php

declare(strict_types=1);

namespace App\Mappers;

use App\DTO\Stripe\StripeEvent;
use App\DTO\Subscription;
use App\DTO\SubscriptionStatus;
use App\Handlers\Stripe\Events\StripeEventType;
use Carbon\Carbon;

class SubscriptionMapper
{
    public static function fromStripeEvent(StripeEvent $event): Subscription
    {
        $data = $event->getData();
        $metadata = $event->getMetadata();

        return match ($event->getType()) {
            StripeEventType::PaymentIntentSucceeded->value => new Subscription(
                gatewayName: 'stripe',
                gatewayCustomerId: $data['customer'],
            ),
            StripeEventType::PaymentIntentPaymentFailed->value => new Subscription(
                gatewayName: 'stripe',
                gatewayCustomerId: $data['customer'],
                description: $data['cancellation_reason'],
            ),
            //            StripeEventType::PaymentIntentRequiresAction->value => new Subscription(
            //                gatewayName: 'stripe',
            //                gatewayCustomerId: $data['customer'],
            //                requiresAction: true,
            //            ),

            StripeEventType::CustomerSubscriptionCreated->value => new Subscription(
                userId: (int)$metadata['user_id'],
                gatewayName: 'stripe',
                gatewayCustomerId: $data['customer'],
                gatewaySubscriptionId: $data['id'],
                status: SubscriptionStatus::mapStripeStatus($data['status']),
                planId: (int)$metadata['plan_id'],
                isNewSubscription: true,
            ),
            StripeEventType::CustomerSubscriptionUpdated->value => new Subscription(
                userId: (int)$metadata['user_id'],
                gatewayName: 'stripe',
                gatewayCustomerId: $data['customer'],
                gatewaySubscriptionId: $data['id'],
                status: SubscriptionStatus::mapStripeStatus($data['status']),
                planId: (int)$metadata['plan_id'],
                currentPeriodEnd: Carbon::createFromTimestamp($data['items']['data'][0]['current_period_end']),
                isUpdated: true,
            ),
            StripeEventType::CustomerSubscriptionDeleted->value => new Subscription(
                userId: (int)$metadata['user_id'],
                gatewayName: 'stripe',
                gatewayCustomerId: $data['customer'],
                gatewaySubscriptionId: $data['id'],
                status: SubscriptionStatus::mapStripeStatus($data['status']),
                planId: (int)$metadata['plan_id'],
                cancelledAt: Carbon::createFromTimestamp($data['canceled_at']),
                isCancelled: true,
            ),
            StripeEventType::InvoicePaymentSucceeded->value => new Subscription(
                gatewayName: 'stripe',
                gatewayCustomerId: $data['customer'],
                gatewaySubscriptionId: $data['parent']['subscription_details']['subscription'],
                status: SubscriptionStatus::ACTIVE,
                currentPeriodEnd: Carbon::createFromTimestamp($data['lines']['data'][0]['period']['end']),
                isPaymentSucceeded: true,
            ),
            StripeEventType::InvoicePaymentFailed->value => new Subscription(
                gatewayName: 'stripe',
                gatewayCustomerId: $data['customer'],
                status: SubscriptionStatus::PAST_DUE,
                isPaymentFailed: true,
            ),

            StripeEventType::CheckoutSessionCompleted->value => new Subscription(
                gatewayName: 'stripe',
                gatewayCustomerId: $data['customer'],
                gatewaySubscriptionId: $data['subscription'],
            ),
            StripeEventType::CustomerCreated->value => new Subscription(
                gatewayName: 'stripe',
                gatewayCustomerId: $data['id'],
            ),
            default => new Subscription
        };
    }

    //    public function fromYoomoneyEvent(YoomoneyEvent $event): Subscription {}
}
