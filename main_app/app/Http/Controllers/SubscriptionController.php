<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\CardException;
use Stripe\Stripe;
use Stripe\StripeClient;

class SubscriptionController extends Controller
{
    public function __construct()
    {
        Stripe::setApikey(config('services.stripe.secret'));
    }

    public function createPaymentIntent(Request $request): JsonResponse
    {
        Log::info('Creating payment intent: ', $request->all());

        $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'plan_slug' => ['required', 'string'],
        ]);

        try {
            $stripe = new StripeClient(config('services.stripe.secret'));

            $user = $request->user();

            //Create or retrieve Stripe customer
            if (!$user->stripe_customer_id) {
                $customer = $stripe->customers->create([
                    'name' => $user->name,
                    'email' => $user->email,
                    'metadata' => [
                        'user_id' => $user->id,
                    ]
                ]);
                $user->update([
                    'stripe_customer_id' => $customer->id,
                ]);
            }
            $customer = $stripe->customers->retrieve($user->stripe_customer_id);

            //create payment intent
            $paymentIntent = $stripe->paymentIntents->create([
                'amount' => $request->amount,
                'currency' => 'usd',
                'customer' => $customer->id,
                'description' => 'Payment for the choosen plan: ' . $request->plan_slug,
                'payment_method_types' => ['card', 'crypto', 'customer_balance', 'paypal'],
                'metadata' => [
                    'user_id' => $user->id,
                    'plan_slug' => $request->plan_slug,
                ],
            ]);

            return response()->json([
                'clientSecret' => $paymentIntent->client_secret,
            ], 201);
        } catch (\Throwable $th) {
            Log::error('Error creating payment intent: ' . $th->getMessage());

            return response()->json([
                'message' => 'Error creating payment intent: ' . $th->getMessage(),
            ], 500);
        }
    }

    /**
     * @param Request $request
     * @param string $slug
     * @return RedirectResponse
     */
    public function subscribe(Request $request, string $slug): RedirectResponse
    {
        Log::info('Subscription request: ', $request->all());

        $request->validate([
            'stripeToken' => ['required', 'string']
        ]);

        $plan = Plan::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $user = $request->user();

        Log::info('Subscription plan: ', $plan->toarray());

        try {
            $stripe = new StripeClient(config('services.stripe.secret'));

            //Create or retrieve Stripe customer
            if (!$user->stripe_customer_id) {
                $customer = $stripe->customers->create([
                    'name' => $user->name,
                    'email' => $user->email,
                    'source' => $request->stripeToken,
                    'metadata' => [
                        'user_id' => $user->id,
                    ]
                ]);
                $user->update([
                    'stripe_customer_id' => $customer->id,
                ]);
            }
            $customer = $stripe->customers->retrieve($user->stripe_customer_id);

            //update customer payment source
            $stripe->customers->update($customer->id, [
                'source' => $request->stripeToken,
            ]);

            //create price for the plan
            $price = $stripe->prices->create([
                'currency' => 'usd',
                'unit_amount' => $plan->price * 100, //cents
                'recurring' => [
                    'interval' => 'month',
                    'interval_count' => 1,
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

            //create subscription
            $subscription = $stripe->subscriptions->create([
                'customer' => $customer->id,
                'items' => [
                    ['price' => $price->id],
                ],
                'metadata' => [
                    'plan_id' => $plan->id,
                    'user_id' => $user->id,
                ],
            ]);

            //update user with subscription
            $user->update([
                'plan_id' => $plan->id,
                'stripe_sub_id' => $subscription->id,
                'pdf_count' => 0,
                'pdf_count_resets_at' => now()->addMonth(),
                'stripe_sub_ends_at' => now()->addMonth(),
            ]);

            return redirect()->route('dashboard')->with('success', 'Subscription activated successfully.');
        } catch (CardException $e) {
            Log::error('Error creating Stripe Card: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error creating Stripe Card. Please try again later.');
        } catch (\Throwable $th) {
            Log::error('Error creating subscription: ' . $th->getMessage());
            return redirect()->back()->with('error', 'Error creating subscription. Please try again later.');
        }
    }

    /**
     * @param Request $request
     * @param string $slug
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createCheckoutSession(Request $request, string $slug): \Symfony\Component\HttpFoundation\Response
    {
        Log::info('Creating checkout session:', $request->all());

        $user = $request->user();

        $plan = Plan::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        try {
            $stripe = new StripeClient(config('services.stripe.secret'));

            $checkoutSession = $stripe->checkout->sessions->create([
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
                    'slug' => $slug,
                ]),
                'metadata' => [
                    'plan_id' => $plan->id,
                    'user_id' => $user->id,
                ],
            ]);

            return Inertia::location($checkoutSession->url);
        } catch (\Throwable $th) {
            return back()->with('error', 'Error creating checkout session: ' . $th->getMessage());
        }
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function successOrFail(Request $request): RedirectResponse
    {
        Log::info('Success request: ', $request->all());
        $sessionId = $request->session()->getId();

        if (!$sessionId) {
            return redirect()->route('dashboard')->with('error', 'Session id not found. Please try again later.');
        }

        try {
            $session = Session::retrieve($sessionId);
            $user = $request->user();

            // Update user with sub details
            $user->update([
                'stripe_customer_id' => $session->customer,
                'stripe_sub_id' => $session->subscription,
                'stripe_sub_ends_at' => now()->addMonth(),
                'plan_id' => $session->metadata->plan_id,
                'pdf_count' => 0,
                'pdf_count_resets_at' => now()->addMonth(),
            ]);

            return redirect()->route('dashboard')->with('success', 'Subscription activated successfully.');
        } catch (\Throwable $th) {
            return redirect()->route('dashboard')->with('error', 'Error creating subscription. Please try again later.');
        }
    }

    public function cancel(Request $request): RedirectResponse
    {
        Log::info('Cancel request: ', $request->all());

        $user = $request->user();

        if (!$user->stripe_customer_id) {
            return redirect()->route('dashboard')->with('error', 'No active subscription found.');
        }

        try {
            $stripe = new StripeClient(config('services.stripe.secret'));
            $stripe->subscriptions->cancel($user->stripe_subscription_id);

            $user->update([
                'stripe_customer_id' => null,
                'stripe_sub_id' => null,
                'stripe_sub_ends_at' => now()->addDays(30), //grace period
            ]);

            return back()->with('success', 'Subscription will be cancelled at the end of the billing period.');
        } catch (\Throwable $th) {
            return back()->with('error', 'Error canceling subscription. Please try again later.');
        }
    }

    public function changePlan(Request $request)
    {
        Log::info('Change plan request: ', $request->all());
        $request->validate([
            'plan_slug' => ['required', 'string', Rule::exists('plans', 'slug')],
        ]);

        $user = $request->user();

        $newPlan = Plan::query()
            ->where('slug', $request->plan_slug)
            ->where('is_active', true)
            ->firstOrFail();

        if (!$user->stripe_sub_id) {
            return back()->with('error', 'No active subscription found.');
        }

        try {
            $stripe = new StripeClient(config('services.stripe.secret'));

            $subscription = $stripe->subscriptions->retrieve($user->stripe_subscription_id);

            $stripe->subscriptions->update($user->stripe_subscription_id, [
                'items' => [[
                    'id' => $subscription->items->data[0]->id,
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => $newPlan->name . ' Plan',
                        ],
                        'unit_amount' => $newPlan->price * 100, //cents
                        'recurring' => [
                            'interval' => 'month',
                            'interval_count' => 1,
                        ],
                    ],
                ]],
                'proration_behavior' => 'create_prorations',
            ]);

            $user->update([
                'plan_id' => $newPlan->id,
                'pdf_count' => 0,
                'pdf_count_resets_at' => now()->addMonth(),
            ]);

            return back()->with('success', 'Plan updated successfully.');
        } catch (\Throwable $th) {
            return back()->with('error', 'Error updating plan. Please try again later.');
        }
    }
}
