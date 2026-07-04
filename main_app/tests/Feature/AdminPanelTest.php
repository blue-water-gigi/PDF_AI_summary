<?php

use App\DTO\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;

it('allows admins to view the users admin panel', function () {
    $plan = Plan::query()->create([
        'name' => 'Basic',
        'slug' => 'basic',
        'description' => 'Basic plan',
        'price' => 0,
        'pdf_limit' => 10,
        'features' => [],
        'is_active' => true,
    ]);

    $admin = User::factory()->create([
        'role' => 'admin',
        'plan_id' => $plan->id,
    ]);

    User::factory()->create([
        'plan_id' => $plan->id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.index'))
        ->assertOk();
});

it('allows admins to assign a plan directly to a user and subscription', function () {
    $basicPlan = Plan::query()->create([
        'name' => 'Basic',
        'slug' => 'basic',
        'description' => 'Basic plan',
        'price' => 0,
        'pdf_limit' => 10,
        'features' => [],
        'is_active' => true,
    ]);

    $premiumPlan = Plan::query()->create([
        'name' => 'Premium',
        'slug' => 'premium',
        'description' => 'Premium plan',
        'price' => 29.99,
        'pdf_limit' => 500,
        'features' => [],
        'is_active' => true,
    ]);

    $admin = User::factory()->create([
        'role' => 'admin',
        'plan_id' => $basicPlan->id,
    ]);

    $user = User::factory()->create([
        'plan_id' => $basicPlan->id,
        'pdf_count' => 8,
    ]);

    Subscription::query()->create([
        'user_id' => $user->id,
        'plan_id' => $basicPlan->id,
        'gateway' => 'admin',
        'gateway_customer_id' => 'admin_user_'.$user->id,
        'gateway_subscription_id' => 'admin_subscription_'.$user->id,
        'status' => SubscriptionStatus::CANCELED,
    ]);

    $this->actingAs($admin)
        ->withSession(['_token' => 'admin-panel-test'])
        ->from(route('admin.index'))
        ->post(route('admin.users.update-plan', $user), [
            '_token' => 'admin-panel-test',
            'plan_id' => $premiumPlan->id,
        ])
        ->assertRedirect(route('admin.index'))
        ->assertSessionHas('success');

    expect($user->fresh()->plan_id)->toBe($premiumPlan->id)
        ->and($user->fresh()->pdf_count)->toBe(0)
        ->and($user->fresh()->subscription->plan_id)->toBe($premiumPlan->id)
        ->and($user->fresh()->subscription->status)->toBe(SubscriptionStatus::ACTIVE)
        ->and($user->fresh()->subscription->current_period_end)->not()->toBeNull();
});