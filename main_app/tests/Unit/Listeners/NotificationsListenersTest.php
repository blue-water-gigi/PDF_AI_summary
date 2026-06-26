<?php

use App\DTO\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('creates only one row in notifications DB', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create([
        'name' => 'Standard',
        'slug' => 'standard',
        'description' => 'Best for regular use',
        'price' => 9.99,
        'pdf_limit' => 50,
        'features' => json_encode([
            '50 PDFs per month',
            'All summaries types',
            'Priority support',
            'Many export options',
            'Advanced analytics',
        ], JSON_THROW_ON_ERROR),
        'is_active' => true,
    ]);

    $sub = Subscription::query()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'gateway' => 'stripe',
        'gateway_customer_id' => 'test',
        'gateway_subscription_id' => 'test',
        'status' => SubscriptionStatus::ACTIVE,
        'current_period_end' => now()->addDay(),
    ]);

    $user = User::query()->find(1);

    $user->notify(new \App\Notifications\SubscriptionActivatedNotification($sub));

    $notifications = $user->notifications;

    expect($user->notifications()->get())->not()->toBeEmpty()
        ->and($notifications->count())->toBe(1);
});

it('creates creates valid row', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create([
        'name' => 'Standard',
        'slug' => 'standard',
        'description' => 'Best for regular use',
        'price' => 9.99,
        'pdf_limit' => 50,
        'features' => json_encode([
            '50 PDFs per month',
            'All summaries types',
            'Priority support',
            'Many export options',
            'Advanced analytics',
        ], JSON_THROW_ON_ERROR),
        'is_active' => true,
    ]);

    $sub = Subscription::query()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'gateway' => 'stripe',
        'gateway_customer_id' => 'test',
        'gateway_subscription_id' => 'test',
        'status' => SubscriptionStatus::ACTIVE,
        'current_period_end' => now()->addDay(),
    ]);

    $user = User::query()->find(1);

    $user->notify(new \App\Notifications\SubscriptionActivatedNotification($sub));

    $notifications = $user->notifications->first();

    expect($notifications->data)->toBe([
        'title' => 'Subscription Activated',
        'message' => 'Your subscription has been activated.',
        'plan_id' => $sub->plan_id,
        'gateway' => $sub->gateway,
        'activated_at' => $sub->current_period_end->toDateTimeString(),
    ]);
});


