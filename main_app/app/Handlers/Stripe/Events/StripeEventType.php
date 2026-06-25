<?php

declare(strict_types=1);

namespace App\Handlers\Stripe\Events;

enum StripeEventType: string
{
    // Payment Processing (Critical)
    case PaymentIntentSucceeded = 'payment_intent.succeeded';
    case PaymentIntentPaymentFailed = 'payment_intent.payment_failed';
    case PaymentIntentRequiresAction = 'payment_intent.requires_action';

    // Subscription Management (Critical)
    case CustomerSubscriptionCreated = 'customer.subscription.created';
    case CustomerSubscriptionUpdated = 'customer.subscription.updated';
    case CustomerSubscriptionDeleted = 'customer.subscription.deleted';
    case InvoicePaymentSucceeded = 'invoice.payment_succeeded';
    case InvoicePaymentFailed = 'invoice.payment_failed';

    // Checkout & Customer Journey
    case CheckoutSessionCompleted = 'checkout.session.completed';
    case CustomerCreated = 'customer.created';
    case PaymentMethodAttached = 'payment_method.attached';

    // Revenue & Financial Tracking
    case InvoiceUpcoming = 'invoice.upcoming';
    case InvoiceFinalized = 'invoice.finalized';
    case PayoutPaid = 'payout.paid';

    // Security & Risk Management
    case ChargeDisputeCreated = 'charge.dispute.created';
    case RadarEarlyFraudWarningCreated = 'radar.early_fraud_warning.created';

    // High-Priority Notifications
    case CustomerSubscriptionTrialWillEnd = 'customer.subscription.trial_will_end';
    case InvoicePaymentActionRequired = 'invoice.payment_action_required';

    /**
     * Get sections for an enum
     */
    public function group(): string
    {
        return match ($this) {
            self::PaymentIntentSucceeded,
            self::PaymentIntentPaymentFailed,
            self::PaymentIntentRequiresAction => 'payment',

            self::CustomerSubscriptionCreated,
            self::CustomerSubscriptionUpdated,
            self::CustomerSubscriptionDeleted => 'subscription',

            self::InvoicePaymentSucceeded,
            self::InvoicePaymentFailed => 'invoice',

            self::CheckoutSessionCompleted,
            self::CustomerCreated,
            self::PaymentMethodAttached => 'checkout',

            self::InvoiceUpcoming,
            self::InvoiceFinalized,
            self::PayoutPaid => 'revenue',

            self::ChargeDisputeCreated,
            self::RadarEarlyFraudWarningCreated => 'security',

            self::CustomerSubscriptionTrialWillEnd,
            self::InvoicePaymentActionRequired => 'notifications',
        };
    }
}
