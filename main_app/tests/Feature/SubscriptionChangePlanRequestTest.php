<?php

use App\DTO\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;

it('rejects the basic plan for active subscription plan changes', function () {
    $this->withoutMiddleware();

    $basicPlan = Plan::query()->create([
        'name' => 'Basic',
        'slug' => 'basic',
        'description' => 'Basic plan',
        'price' => 0,
        'pdf_limit' => 10,
        'features' => [],
        'is_active' => true,
    ]);

    $standardPlan = Plan::query()->create([
        'name' => 'Standard',
        'slug' => 'standard',
        'description' => 'Standard plan',
        'price' => 9.99,
        'pdf_limit' => 50,
        'features' => [],
        'is_active' => true,
    ]);

    $user = User::factory()->create(['plan_id' => $standardPlan->id]);

    Subscription::query()->create([
        'user_id' => $user->id,
        'plan_id' => $standardPlan->id,
        'gateway' => 'stripe',
        'gateway_customer_id' => 'cus_test_123',
        'gateway_subscription_id' => 'sub_test_123',
        'status' => SubscriptionStatus::ACTIVE,
        'current_period_end' => now()->addMonth(),
    ]);

    $this->actingAs($user)
        ->from(route('subscription.settings'))
        ->patch(route('subscription.update'), [
            'new_plan_id' => $basicPlan->id,
            'gateway' => 'stripe',
        ])
        ->assertRedirect(route('subscription.settings'))
        ->assertSessionHasErrors('new_plan_id');
});