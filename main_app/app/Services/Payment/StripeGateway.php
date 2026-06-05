<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Exceptions\Payment\CustomerException;
use App\Exceptions\Payment\PaymentSessionException;
use App\Exceptions\Payment\PlanPriceException;
use App\Exceptions\Payment\SubscriptionException;
use App\Models\Plan;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\CardException;
use Stripe\Price;
use Stripe\Stripe;
use Stripe\StripeClient;
use Throwable;

readonly class StripeGateway implements PaymentGatewayInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct(private StripeClient $stripeClient)
    {
    }

    /**
     * Create Price object for subscription
     *
     * @throws PlanPriceException
     */
    private function createPlanPrice(
        Plan   $plan,
        string $currency = 'usd',
        string $interval = 'month',
        int    $intervalCount = 1): Price
    {
        try {
            return $this->stripeClient->prices->create([
                'currency' => $currency,
                'unit_amount' => $plan->price * 100, // cents
                'recurring' => [
                    'interval' => $interval,
                    'interval_count' => $intervalCount,
                ],
                'product_data' => [
                    'name' => $plan->name,
                    'active' => $plan->is_active,
                    'metadata' => [
                        'plan_id' => $plan->id,
                        'description' => $plan->description,
                    ],
                ],
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Stripe Api error creating Price object: ' . $e->getMessage());

            throw new PlanPriceException('stripe', 'Stripe Api error creating Price object: ' . $e->getMessage(), 500, $e);
        }
    }

    /**
     * Create subscription
     *
     * @return string Suscription id
     *
     * @throws ApiErrorException
     * @throws CustomerException
     * @throws PlanPriceException
     * @throws SubscriptionException
     */
    public function createSubscription(User $user, Plan $plan): string
    {
        Log::info('Subscription plan: ', $plan->toArray());

        $customer = $this->createOrRetrieveCustomer($user);
        $price = $this->createPlanPrice($plan, 'usd', 'month', 1);

        try {
            $subscription = $this->stripeClient->subscriptions->create([
                'customer' => $customer,
                'items' => [
                    ['price' => $price->id],
                ],
                'metadata' => [
                    'plan_id' => $plan->id,
                    'user_id' => $user->id,
                ],
            ]);

            return $subscription->id;
        } catch (CardException $e) {
            Log::error('Error handling customer card: ' . $e->getMessage());

            throw new SubscriptionException('stripe', 'Api error handling customer card: ' . $e->getMessage(), 500, $e);
        }
    }

    /**
     * Create or retrieves Stripe customer
     *
     * @param string|null $stripeToken passing this will create a new source object, make it the new customer default source, and delete the old customer default if one exists
     * @return string customer's id
     *
     * @throws CustomerException
     */
    public function createOrRetrieveCustomer(User $user, ?string $stripeToken = null): string
    {
        try {
            if (!$user->stripe_customer_id) {
                $customer = $this->stripeClient->customers->create([
                    'name' => $user->name,
                    'email' => $user->email,
                    'source' => $stripeToken,
                    'metadata' => [
                        'user_id' => $user->id,
                    ],
                ]);
            } else {
                $customer = $this->stripeClient->customers->retrieve($user->stripe_customer_id);
            }

            return $customer->id;
        } catch (ApiErrorException $e) {
            Log::error('Stripe Api error creating or receiving customer: ' . $e->getMessage());

            throw new CustomerException('stripe', 'Stripe Api error creating or receiving customer: ' . $e->getMessage(), 500, $e);
        }
    }

    /**
     * Cancel subscription
     *
     * @throws SubscriptionException
     */
    public function cancelSubscription(string $subscriptionId): void
    {
        try {
            $this->stripeClient->subscriptions->cancel($subscriptionId);
        } catch (Throwable $th) {
            Log::error('Stripe Api error canceling subscription: ' . $th->getMessage());

            throw new SubscriptionException('stripe', 'Api error canceling subscription: ' . $th->getMessage(), 500, $th);
        }
    }

    /**
     * Change subscribtion plan
     *
     * @throws ApiErrorException
     * @throws SubscriptionException
     */
    public function changePlan(
        string $subscriptionId,
        Plan   $plan,
        string $currency = 'usd',
        string $interval = 'month',
        int    $intervalCount = 1
    ): void
    {
        $subscription = $this->stripeClient->subscriptions->retrieve($subscriptionId);

        try {
            $this->stripeClient->subscriptions->update($subscriptionId, [
                'items' => [[
                    'id' => $subscription->items->data[0]->id,
                    'price_data' => [
                        'currency' => $currency,
                        'product_data' => [
                            'name' => $plan->name . ' Plan',
                        ],
                        'unit_amount' => $plan->price * 100, // cents
                        'recurring' => [
                            'interval' => $interval,
                            'interval_count' => $intervalCount,
                        ],
                    ],
                ]],
                'proration_behavior' => 'create_prorations',
            ]);
        } catch (Throwable $th) {
            Log::error('Stripe Api error changing plan: ' . $th->getMessage());

            throw new SubscriptionException('stripe', 'Api error changing plan: ' . $th->getMessage(), 500, $th);
        }
    }

    /**
     * Set and get current subscription data
     */
    public function setSubscriptionData(?string $subscriptionId, ?string $customerId, CarbonInterface $endsAt): iterable
    {
        return [
            'stripe_customer_id' => $customerId,
            'stripe_sub_id' => $subscriptionId,
            'sub_ends_at' => $endsAt,
        ];
    }

    public function getGatewayName(): string
    {
        return 'stripe';
    }

    /**
     * get user subscription id from DB
     *
     * @throws SubscriptionException
     */
    public function getSubscriptionId(User $user): string
    {
        return $user->stripe_sub_id ?? throw new SubscriptionException(
            'stripe',
            'No subscription found for this user.',
        );
    }

    /** Create payment intent for subscription
     *
     * Use this if you intend to create your own frontend for paymentIntent page
     *
     * @param User $user
     * @param int $amount Amount for payment.
     * @param string $planSlug Plan slug for plan.
     * @return string|null Token used for client-side retrieval using a publishable key.
     * @throws ApiErrorException
     * @throws RuntimeException
     * @throws PaymentIntentException
     **/
//    private function createPaymentIntent(User $user, int $amount, string $planSlug): ?string
//    {
//        try {
//            //create of retrieve customer
//            if (!$user->stripe_customer_id) {
//                $customer = $this->stripeClient->customers->create([
//                    'name' => $user->name,
//                    'email' => $user->email,
//                    'metadata' => [
//                        'user_id' => $user->id,
//                    ]
//                ]);
//            } else {
//                $customer = $this->stripeClient->customers->retrieve($user->stripe_customer_id);
//            }
//
//            $paymentIntent = $this->stripeClient->paymentIntents->create([
//                'amount' => $amount,
//                'currency' => 'usd',
//                'customer' => $customer->id,
//                'description' => 'Payment for the choosen plan: ' . $planSlug,
//                'payment_method_types' => ['card', 'crypto', 'customer_balance', 'paypal'],
//                'metadata' => [
//                    'user_id' => $user->id,
//                    'plan_slug' => $planSlug,
//                ],
//            ]);
//
//            return $paymentIntent->client_secret;
//        } catch (ApiErrorException $e) {
//            Log::error('Stripe Api error creating payment intent: ' . $e->getMessage());
//
//            throw new PaymentIntentException(
//                'stripe',
//                'Stripe Api error creating payment intent: ' . $e->getMessage(),
//                500);
//        }
//    }

    /**
     *      Create session for subscription
     *
     *     Use this if you intend to use default frontend stripe page
     *
     * @param User $user
     * @param Plan $plan
     * @return string|null Session url.
     * @throws PaymentSessionException
     **/
    public function createCheckoutSession(User $user, Plan $plan): ?string
    {
        try {
            $checkoutSession = $this->stripeClient->checkout->sessions->create([
                'payment_method_types' => ['card', 'crypto', 'customer_balance', 'paypal'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => $plan->name . 'Plan',
                            'description' => $plan->description,
                        ],
                        'unit_amount' => $plan->price * 100, //cents
                        'recurring' => [
                            'interval' => 'month',
                            'interval_count' => 1,
                        ]
                    ],
                    'quantity' => 1,
                ]],
                'customer_email' => $user->email,
                'client_reference_id' => $user->id,
                'mode' => 'subscription',
                'success_url' => route('subscription.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('checkout', [
                    'slug' => $plan->slug,
                ]),
                'metadata' => [
                    'plan_id' => $plan->id,
                    'user_id' => $user->id,
                ],
            ]);

            return $checkoutSession->url;
        } catch
        (ApiErrorException $e) {
            Log::error('Stripe Api error creating payment session: ' . $e->getMessage());

            throw new PaymentSessionException(
                'stripe',
                'Stripe Api error creating payment session: ' . $e->getMessage(),
                500
            );
        }
    }
}
