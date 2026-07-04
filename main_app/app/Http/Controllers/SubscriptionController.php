<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Subscription\ChangePlanRequest;
use App\Http\Requests\Subscription\DestroySubscriptionRequest;
use App\Http\Requests\Subscription\StoreSubscriptionRequest;
use App\Mappers\SubscriptionViewMapper;
use App\Models\Plan;
use App\Models\User;
use App\Services\Payment\PaymentGatewayFactory;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayFactory  $factory,
        private readonly SubscriptionViewMapper $subscriptionViewMapper,
    )
    {
    }

    public function index(): InertiaResponse
    {
        $user = Auth::user();
        $subscriptionData = $user instanceof User ? $this->subscriptionViewMapper->sharedProps($user) : null;

        return Inertia::render('welcome', [
            'canRegister' => true,
            'plans' => $subscriptionData['plans'] ?? $this->subscriptionViewMapper->plansForFrontend(),
            'currentPlanSlug' => $subscriptionData['currentPlanSlug'] ?? null,
            'auth' => [
                'user' => $user,
            ],
            'userStats' => $subscriptionData['userStats'] ?? null,
        ]);
    }

    public function dashboard(): InertiaResponse
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return Inertia::render('dashboard', $this->subscriptionViewMapper->dashboardProps($user));
    }

    public function settings(): InertiaResponse
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return Inertia::render('settings/subscription', $this->subscriptionViewMapper->settingsProps($user));
    }

    public function store(StoreSubscriptionRequest $request): SymfonyResponse
    {
        $validated = $request->validated();
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        $plan = Plan::query()->findOrFail($validated['plan_id']);

        try {
            $service = $this->makeSubscriptionService(
                $validated['gateway'] ?? config('payment.default_gateway')
            );

            $checkoutUrl = $service->subscribe($user, $plan);

            return Inertia::location($checkoutUrl);
        } catch (Throwable $th) {
            Log::error('Error creating subscription', [
                'message' => $th->getMessage(),
                'code' => $th->getCode(),
                'line' => $th->getLine(),
                'file' => $th->getFile(),
                'trace' => $th->getTraceAsString(),
                'user_id' => $user->id,
                'plan_id' => $plan->id,
            ]);

            return back()->withErrors([
                'subscription' => 'Something went wrong while trying to subscribe to this plan. Please try again later.',
            ]);
        }
    }

    public function update(ChangePlanRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        $newPlan = Plan::query()->findOrFail($validated['new_plan_id']);

        try {
            $service = $this->makeSubscriptionService(
                $validated['gateway'] ?? config('payment.default_gateway')
            );

            $service->changePlan($user, $newPlan);

            return to_route('subscription.settings')->with('success', 'Plan updated successfully.');
        } catch (Throwable $th) {
            Log::error('Error updating subscription', [
                'message' => $th->getMessage(),
                'code' => $th->getCode(),
                'line' => $th->getLine(),
                'file' => $th->getFile(),
                'trace' => $th->getTraceAsString(),
                'user_id' => $user->id,
                'plan_id' => $newPlan->id,
            ]);

            return back()->withErrors([
                'subscription_update' => 'Something went wrong while trying to update current plan. Please try again later.',
            ]);
        }
    }

    public function destroy(DestroySubscriptionRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        $plan = $user->plan;

        try {
            $service = $this->makeSubscriptionService(
                $validated['gateway'] ?? config('payment.default_gateway')
            );

            $service->cancel($user);

            return to_route('subscription.settings')->with('success', 'Unsubscribed successfully.');
        } catch (Throwable $th) {
            Log::error('Error canceling subscription', [
                'message' => $th->getMessage(),
                'code' => $th->getCode(),
                'line' => $th->getLine(),
                'file' => $th->getFile(),
                'trace' => $th->getTraceAsString(),
                'user_id' => $user->id,
                'plan_id' => $plan?->id,
            ]);

            return back()->withErrors([
                'subscription_update' => 'Something went wrong while trying to cancel current plan subscription. Please try again later.',
            ]);
        }
    }

    /**
     * Generate a SubscriptionService class instance.
     *
     * @throws BindingResolutionException
     */
    private function makeSubscriptionService(string $gateway): SubscriptionService
    {
        return new SubscriptionService($this->factory->resolve($gateway));
    }
}
