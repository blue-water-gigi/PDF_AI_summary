<?php

use App\DTO\SubscriptionStatus;
use App\Exceptions\SubscriptionModelException;
use App\Models\Subscription;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\Subscription\SubscriptionWebhookService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);
beforeEach(function () {
    $this->seed();
});

test('Service layer correctly handles Subscription cancel event, creates\updates subscriptions table in DB', function (
    string $gateway,
) {
    // get real data
    $data = getFromCustomerSubscriptionDeleted()['object'];
    $metadata = getFromCustomerSubscriptionDeleted()['object']['metadata'];
    $metadata['user_id'] = 1;
    $metadata['plan_id'] = 3;

    // create subscription (mock that it already exists)
    $sub = Subscription::create([
        'user_id' => 1,
        'plan_id' => 3,
        'gateway' => $gateway,
        'gateway_customer_id' => $data['customer'],
        'gateway_subscription_id' => $data['id'],
        'status' => SubscriptionStatus::ACTIVE,
        'current_period_end' => $originalPeriodEnd = now()->addMonth(),
        'cancelled_at' => null,
        'trial_ends_at' => null,
    ]);

    $originalPlanId = $sub->plan_id;

    $dto = new App\DTO\Subscription(
        userId: $metadata['user_id'],
        gatewayName: 'stripe',
        gatewayCustomerId: $data['customer'],
        gatewaySubscriptionId: $data['id'],
        status: SubscriptionStatus::mapStripeStatus($data['status']),
        planId: $metadata['plan_id'],
        cancelledAt: Carbon::createFromTimestamp($data['canceled_at']),
        isCancelled: true,
    );

    $service = app()->make(SubscriptionWebhookService::class);

    $gateway === 'stripe' ? $service->syncWithStripe($dto) : $service->syncWithYoomoney($dto);

    $freshSub = Subscription::where('gateway_customer_id', $dto->gatewayCustomerId)->first();

    expect($freshSub->status)->toBe(SubscriptionStatus::CANCELED)
        ->and($freshSub->cancelled_at)->not()->toBeNull()
        ->and($freshSub->plan_id)->toBe($originalPlanId)
        ->and($freshSub->current_period_end->format('Y-m-d H:i:s'))->toEqual($originalPeriodEnd->format('Y-m-d H:i:s'));
})
    ->with(['stripe']);

test('Service layer correctly handles payment failed event, creates\updates subscriptions table in DB', function (
    string $gateway,
) {
    // get real data
    $data = getFromInvoicePaymentFailed()['object'];
    $metadata = getFromInvoicePaymentFailed()['object']['metadata'];
    $metadata['user_id'] = 1;
    $metadata['plan_id'] = 3;

    // create subscription (mock that it already exists)
    $sub = Subscription::create([
        'user_id' => 1,
        'plan_id' => 3,
        'gateway' => $gateway,
        'gateway_customer_id' => $data['customer'],
        'gateway_subscription_id' => 'sub_test',
        'status' => SubscriptionStatus::ACTIVE,
        'current_period_end' => $originalPeriodEnd = now()->addMonth(),
        'cancelled_at' => null,
        'trial_ends_at' => null,
    ]);

    $originalPlanId = $sub->plan_id;

    $dto = new App\DTO\Subscription(
        userId: $metadata['user_id'],
        gatewayName: 'stripe',
        gatewayCustomerId: $data['customer'],
        status: SubscriptionStatus::PAST_DUE,
        isPaymentFailed: true,
    );

    $service = app()->make(SubscriptionWebhookService::class);

    $gateway === 'stripe' ? $service->syncWithStripe($dto) : $service->syncWithYoomoney($dto);

    $freshSub = Subscription::where('gateway_customer_id', $dto->gatewayCustomerId)->first();

    expect($freshSub->status)->toBe(SubscriptionStatus::PAST_DUE)
        ->and($freshSub->cancelled_at)->toBeNull()
        ->and($freshSub->plan_id)->toBe($originalPlanId)
        ->and($freshSub->current_period_end->format('Y-m-d H:i:s'))->toEqual($originalPeriodEnd->format('Y-m-d H:i:s'));
})
    ->with(['stripe']);

test('Service layer correctly handles payment succeeded event, creates\updates subscriptions table in DB', function (
    string $gateway,
) {
    // get real data
    $data = getFromInvoicePaymentSucceeded()['object'];
    $metadata = getFromInvoicePaymentSucceeded()['object']['metadata'];
    $metadata['user_id'] = 1;
    $metadata['plan_id'] = 3;

    // create subscription (mock that it already exists)
    $sub = Subscription::create([
        'user_id' => 1,
        'plan_id' => 3,
        'gateway' => $gateway,
        'gateway_customer_id' => $data['customer'],
        'gateway_subscription_id' => 'sub_test',
        'status' => SubscriptionStatus::PAST_DUE,
        'current_period_end' => $originalPeriodEnd = now()->addMonth(),
        'cancelled_at' => null,
        'trial_ends_at' => null,
    ]);

    $originalPlanId = $sub->plan_id;

    $dto = new App\DTO\Subscription(
        userId: $metadata['user_id'],
        gatewayName: 'stripe',
        gatewayCustomerId: $data['customer'],
        status: SubscriptionStatus::ACTIVE,
        currentPeriodEnd: Carbon::createFromTimestamp($data['lines']['data'][0]['period']['end']),
        isPaymentSucceeded: true,
    );

    $service = app()->make(SubscriptionWebhookService::class);

    $gateway === 'stripe' ? $service->syncWithStripe($dto) : $service->syncWithYoomoney($dto);

    $freshSub = Subscription::where('gateway_customer_id', $dto->gatewayCustomerId)->first();

    expect($freshSub->status)->toBe(SubscriptionStatus::ACTIVE)
        ->and($freshSub->cancelled_at)->toBeNull()
        ->and($freshSub->plan_id)->toBe($originalPlanId)
        ->and($freshSub->current_period_end->format('Y-m-d H:i:s'))->not()->toEqual($originalPeriodEnd->format('Y-m-d H:i:s'))
        ->and($freshSub->user->pdf_count)->toBe(0)
        ->and($freshSub->user->pdf_count_resets_at)->toEqual($freshSub->current_period_end);
})
    ->with(['stripe']);

test('Service layer correctly handles subscription updated event, creates\updates subscriptions table in DB', function (
    string $gateway,
) {
    // get real data
    $data = getFromCustomerSubscriptionUpdated()['object'];
    $metadata = getFromCustomerSubscriptionUpdated()['object']['metadata'];
    $metadata['user_id'] = 1;
    $metadata['plan_id'] = 3;

    // create subscription (mock that it already exists)
    $sub = Subscription::create([
        'user_id' => 1,
        'plan_id' => 2,
        'gateway' => $gateway,
        'gateway_customer_id' => $data['customer'],
        'gateway_subscription_id' => 'sub_test',
        'status' => SubscriptionStatus::PAST_DUE,
        'current_period_end' => now()->addMonth(),
        'cancelled_at' => null,
        'trial_ends_at' => null,
    ]);

    $dto = new App\DTO\Subscription(
        userId: $metadata['user_id'],
        gatewayName: 'stripe',
        gatewayCustomerId: $data['customer'],
        gatewaySubscriptionId: $data['id'],
        status: SubscriptionStatus::mapStripeStatus($data['status']),
        planId: $metadata['plan_id'],
        currentPeriodEnd: Carbon::createFromTimestamp($data['items']['data'][0]['current_period_end']),
        isUpdated: true,
    );

    $service = app()->make(SubscriptionWebhookService::class);

    $gateway === 'stripe' ? $service->syncWithStripe($dto) : $service->syncWithYoomoney($dto);

    $freshSub = Subscription::where('gateway_customer_id', $dto->gatewayCustomerId)->first();

    expect($freshSub->status)->toBe(SubscriptionStatus::ACTIVE)
        ->and($freshSub->cancelled_at)->toBeNull()
        ->and($freshSub->plan_id)->toEqual($dto->planId)
        ->and($freshSub->gateway)->toEqual($dto->gatewayName)
        ->and($freshSub->gateway_subscription_id)->toEqual($dto->gatewaySubscriptionId)
        ->and($freshSub->status)->toEqual($dto->status)
        ->and($freshSub->current_period_end->format('Y-m-d H:i:s'))->toEqual($dto->currentPeriodEnd->format('Y-m-d H:i:s'));
})
    ->with(['stripe']);

test('Service layer correctly handles new subscription created event, creates\updates subscriptions table in DB',
    function (
        string $gateway,
    ) {
        // get real data
        $data = getFromCustomerSubscriptionCreated()['object'];
        $metadata = getFromCustomerSubscriptionCreated()['object']['metadata'];
        $metadata['user_id'] = 1;
        $metadata['plan_id'] = 3;

        $dto = new App\DTO\Subscription(
            userId: $metadata['user_id'],
            gatewayName: 'stripe',
            gatewayCustomerId: $data['customer'],
            gatewaySubscriptionId: $data['id'],
            status: SubscriptionStatus::mapStripeStatus($data['status']),
            planId: $metadata['plan_id'],
            isNewSubscription: true,
        );

        $service = app()->make(SubscriptionWebhookService::class);

        $gateway === 'stripe'
            ? $service->syncWithStripe($dto)
            : $service->syncWithYoomoney($dto);

        $sub = Subscription::where('gateway_customer_id', $dto->gatewayCustomerId)->first();

        expect($sub->status)->toBe(SubscriptionStatus::ACTIVE)
            ->and($sub->cancelled_at)->toBeNull()
            ->and($sub->plan_id)->toEqual($dto->planId)
            ->and($sub->user_id)->toEqual($dto->userId)
            ->and($sub->gateway_customer_id)->toEqual($dto->gatewayCustomerId)
            ->and($sub->trial_ends_at)->toBeNull()
            ->and($sub->gateway)->toEqual($dto->gatewayName)
            ->and($sub->gateway_subscription_id)->toEqual($dto->gatewaySubscriptionId)
            ->and($sub->status)->toEqual($dto->status)
            ->and($sub->current_period_end)->toBeNull(); // comes from payment success event
    })
    ->with(['stripe']);

it('throws SubscriptionModelException when there is no subscription but we try to do something with it',
    function (string $gateway) {
        // get real data
        $data = getFromCustomerSubscriptionDeleted()['object'];
        $metadata = getFromCustomerSubscriptionDeleted()['object']['metadata'];
        $metadata['user_id'] = 1;
        $metadata['plan_id'] = 3;

        $dto = new App\DTO\Subscription(
            userId: $metadata['user_id'],
            gatewayName: 'stripe',
            gatewayCustomerId: $data['customer'],
            gatewaySubscriptionId: $data['id'],
            status: SubscriptionStatus::mapStripeStatus($data['status']),
            planId: $metadata['plan_id'],
            cancelledAt: Carbon::createFromTimestamp($data['canceled_at']),
            isCancelled: true,
        );

        $service = app()->make(SubscriptionWebhookService::class);

        $gateway === 'stripe' ? $service->syncWithStripe($dto) : $service->syncWithYoomoney($dto);

    })
    ->with(['stripe'])
    ->throws(SubscriptionModelException::class)
    ->after(function () {
        expect(Subscription::all())->toBeEmpty();
    });

it('executes operations within transaction', function (string $gateway) {
    $data = getFromInvoicePaymentSucceeded()['object'];
    $metadata = getFromInvoicePaymentSucceeded()['object']['metadata'];
    $metadata['user_id'] = 1;
    $metadata['plan_id'] = 3;

    $user = User::query()->findOrFail(1);
    $user->update(['pdf_count' => 5]);

    Subscription::create([
        'user_id' => 1,
        'plan_id' => 3,
        'gateway' => $gateway,
        'gateway_customer_id' => $data['customer'],
        'gateway_subscription_id' => 'sub_test',
        'status' => SubscriptionStatus::PAST_DUE,
        'current_period_end' => $originalPeriodEnd = now()->addMonth(),
        'cancelled_at' => null,
        'trial_ends_at' => null,
    ]);

    $userMock = Mockery::mock($user)->makePartial();
    $userMock->shouldReceive('update')
        ->once()
        ->andThrow(new RuntimeException('Simulated failure during user update'));

    $userRepository = Mockery::mock(UserRepository::class);
    $userRepository->shouldReceive('findById')
        ->with(1)
        ->andReturn($userMock);

    $this->app->instance(UserRepository::class, $userRepository);

    $dto = new App\DTO\Subscription(
        userId: $metadata['user_id'],
        gatewayName: 'stripe',
        gatewayCustomerId: $data['customer'],
        status: SubscriptionStatus::ACTIVE,
        currentPeriodEnd: Carbon::createFromTimestamp($data['lines']['data'][0]['period']['end']),
        isPaymentSucceeded: true,
    );

    $service = app()->make(SubscriptionWebhookService::class);

    expect(fn() => $gateway === 'stripe'
        ? $service->syncWithStripe($dto)
        : $service->syncWithYoomoney($dto))
        ->toThrow(RuntimeException::class, 'Simulated failure during user update');

    $freshSub = Subscription::where('gateway_customer_id', $dto->gatewayCustomerId)->first();
    $freshUser = User::query()->findOrFail(1);

    expect($freshSub->status)->toBe(SubscriptionStatus::PAST_DUE)
        ->and($freshSub->current_period_end->format('Y-m-d H:i:s'))->toEqual($originalPeriodEnd->format('Y-m-d H:i:s'))
        ->and($freshUser->pdf_count)->toBe(5);
})->with(['stripe']);

it('throws exception outside of repository when user not found within DB', function (string $gateway) {
    $data = getFromCustomerSubscriptionUpdated()['object'];
    $metadata = getFromCustomerSubscriptionUpdated()['object']['metadata'];
    $metadata['user_id'] = 1;
    $metadata['plan_id'] = 3;

    $sub = Subscription::create([
        'user_id' => 1,
        'plan_id' => 2,
        'gateway' => $gateway,
        'gateway_customer_id' => 'doesnt_exists',
        'gateway_subscription_id' => 'sub_test',
        'status' => SubscriptionStatus::PAST_DUE,
        'current_period_end' => now()->addMonth(),
        'cancelled_at' => null,
        'trial_ends_at' => null,
    ]);

    $dto = new App\DTO\Subscription(
        userId: 33,
        gatewayName: 'stripe',
        gatewayCustomerId: 'random_id',
        gatewaySubscriptionId: $data['id'],
        status: SubscriptionStatus::mapStripeStatus($data['status']),
        planId: $metadata['plan_id'],
        currentPeriodEnd: Carbon::createFromTimestamp($data['items']['data'][0]['current_period_end']),
        isUpdated: true,
    );

    $service = app()->make(SubscriptionWebhookService::class);

    $gateway === 'stripe' ? $service->syncWithStripe($dto) : $service->syncWithYoomoney($dto);
})->with(['stripe'])
    ->throws(ModelNotFoundException::class);
