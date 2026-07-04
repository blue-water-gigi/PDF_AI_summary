<?php

use App\Contracts\PaymentGatewayInterface;
use App\DTO\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Subscription\SubscriptionService;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('creates an incomplete subscription record before redirecting to checkout', function () {
    $basicPlan = Plan::query()->create([
        'name' => 'Basic',
        'slug' => 'basic',
        'description' => 'Basic plan',
        'price' => 0,
        'pdf_limit' => 10,
        'features' => [],
        'is_active' => true,
    ]);

    $paidPlan = Plan::query()->create([
        'name' => 'Standard',
        'slug' => 'standard',
        'description' => 'Standard plan',
        'price' => 9.99,
        'pdf_limit' => 50,
        'features' => [],
        'is_active' => true,
    ]);

    $user = User::factory()->create(['plan_id' => $basicPlan->id]);
    $gateway = new class implements PaymentGatewayInterface {
        public function createOrRetrieveCustomer(User $user): string
        {
            return 'cus_test_123';
        }

        public function createCheckoutSession(User $user, Plan $plan): ?string
        {
            expect($user->subscription)->not()->toBeNull()
                ->and($user->subscription->gateway_customer_id)->toBe('cus_test_123')
                ->and($plan->slug)->toBe('standard');

            return 'https://checkout.stripe.test/session';
        }

        public function cancelSubscription(string $subscriptionId, User $user): void
        {
        }

        public function changePlan(string $subscriptionId, Plan $plan, User $user): void
        {
        }

        public function setSubscriptionData(?string $subscriptionId = null, ?string $customerId = null, ?CarbonInterface $endsAt = null): array
        {
            return array_filter([
                'gateway_subscription_id' => $subscriptionId,
                'gateway_customer_id' => $customerId,
                'current_period_end' => $endsAt,
            ], fn (string|CarbonInterface|null $value) => ! is_null($value));
        }

        public function getGatewayName(): string
        {
            return 'stripe';
        }

        public function getSubscriptionId(User $user): string
        {
            return 'sub_test_123';
        }
    };

    $url = (new SubscriptionService($gateway))->subscribe($user, $paidPlan);

    $subscription = Subscription::query()->where('user_id', $user->id)->firstOrFail();

    expect($url)->toBe('https://checkout.stripe.test/session')
        ->and($subscription->gateway)->toBe('stripe')
        ->and($subscription->plan_id)->toBe($paidPlan->id)
        ->and($subscription->gateway_customer_id)->toBe('cus_test_123')
        ->and($subscription->gateway_subscription_id)->toBe('checkout_pending_'.$user->id)
        ->and($subscription->status)->toBe(SubscriptionStatus::INCOMPLETE);
});

it('delegates plan changes to the payment gateway without changing local subscription state', function () {
    $currentPlan = Plan::query()->create([
        'name' => 'Standard',
        'slug' => 'standard',
        'description' => 'Standard plan',
        'price' => 9.99,
        'pdf_limit' => 50,
        'features' => [],
        'is_active' => true,
    ]);

    $newPlan = Plan::query()->create([
        'name' => 'Premium',
        'slug' => 'premium',
        'description' => 'Premium plan',
        'price' => 29.99,
        'pdf_limit' => 500,
        'features' => [],
        'is_active' => true,
    ]);

    $user = User::factory()->create([
        'plan_id' => $currentPlan->id,
        'pdf_count' => 12,
        'pdf_count_resets_at' => now()->addMonth(),
    ]);

    Subscription::query()->create([
        'user_id' => $user->id,
        'plan_id' => $currentPlan->id,
        'gateway' => 'stripe',
        'gateway_customer_id' => 'cus_test_123',
        'gateway_subscription_id' => 'sub_test_123',
        'status' => SubscriptionStatus::ACTIVE,
        'current_period_end' => now()->addMonth(),
    ]);

    $gateway = new class implements PaymentGatewayInterface {
        public bool $changed = false;

        public function createOrRetrieveCustomer(User $user): string
        {
            return 'cus_test_123';
        }

        public function createCheckoutSession(User $user, Plan $plan): ?string
        {
            return null;
        }

        public function cancelSubscription(string $subscriptionId, User $user): void
        {
        }

        public function changePlan(string $subscriptionId, Plan $plan, User $user): void
        {
            expect($subscriptionId)->toBe('sub_test_123')
                ->and($plan->slug)->toBe('premium')
                ->and($user->id)->not()->toBeNull();

            $this->changed = true;
        }

        public function setSubscriptionData(?string $subscriptionId = null, ?string $customerId = null, ?CarbonInterface $endsAt = null): array
        {
            return [];
        }

        public function getGatewayName(): string
        {
            return 'stripe';
        }

        public function getSubscriptionId(User $user): string
        {
            return $user->subscription?->gateway_subscription_id ?? 'missing';
        }
    };

    (new SubscriptionService($gateway))->changePlan($user, $newPlan);

    expect($gateway->changed)->toBeTrue()
        ->and($user->fresh()->plan_id)->toBe($currentPlan->id)
        ->and($user->fresh()->pdf_count)->toBe(12)
        ->and($user->subscription()->firstOrFail()->plan_id)->toBe($currentPlan->id);
});

it('delegates cancellation to the payment gateway without changing local subscription state', function () {
    $plan = Plan::query()->create([
        'name' => 'Standard',
        'slug' => 'standard',
        'description' => 'Standard plan',
        'price' => 9.99,
        'pdf_limit' => 50,
        'features' => [],
        'is_active' => true,
    ]);

    $user = User::factory()->create([
        'plan_id' => $plan->id,
        'pdf_count' => 7,
        'pdf_count_resets_at' => now()->addMonth(),
    ]);

    Subscription::query()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'gateway' => 'stripe',
        'gateway_customer_id' => 'cus_test_123',
        'gateway_subscription_id' => 'sub_test_123',
        'status' => SubscriptionStatus::ACTIVE,
        'current_period_end' => now()->addMonth(),
    ]);

    $gateway = new class implements PaymentGatewayInterface {
        public bool $cancelled = false;

        public function createOrRetrieveCustomer(User $user): string
        {
            return 'cus_test_123';
        }

        public function createCheckoutSession(User $user, Plan $plan): ?string
        {
            return null;
        }

        public function cancelSubscription(string $subscriptionId, User $user): void
        {
            expect($subscriptionId)->toBe('sub_test_123')
                ->and($user->id)->not()->toBeNull();

            $this->cancelled = true;
        }

        public function changePlan(string $subscriptionId, Plan $plan, User $user): void
        {
        }

        public function setSubscriptionData(?string $subscriptionId = null, ?string $customerId = null, ?CarbonInterface $endsAt = null): array
        {
            return [];
        }

        public function getGatewayName(): string
        {
            return 'stripe';
        }

        public function getSubscriptionId(User $user): string
        {
            return $user->subscription?->gateway_subscription_id ?? 'missing';
        }
    };

    (new SubscriptionService($gateway))->cancel($user);

    $subscription = $user->subscription()->firstOrFail();

    expect($gateway->cancelled)->toBeTrue()
        ->and($user->fresh()->plan_id)->toBe($plan->id)
        ->and($user->fresh()->pdf_count)->toBe(7)
        ->and($subscription->status)->toBe(SubscriptionStatus::ACTIVE)
        ->and($subscription->cancelled_at)->toBeNull();
});
