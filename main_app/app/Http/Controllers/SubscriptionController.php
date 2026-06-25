<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Subscription\ChangePlanRequest;
use App\Http\Requests\Subscription\DestroySubscriptionRequest;
use App\Http\Requests\Subscription\StoreSubscriptionRequest;
use App\Models\Plan;
use App\Services\Payment\PaymentGatewayFactory;
use App\Services\SubscriptionService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Throwable;

class SubscriptionController extends Controller
{
    public function __construct(private readonly PaymentGatewayFactory $factory) {}

    public function index(): InertiaResponse
    {
        $user = Auth::user();
        $userStats = null;

        if ($user) {
            $user->load('plan');
            $userStats = [
                'pdfCount' => $user->pdf_count,
                'pdfLimit' => $user->plan?->pdf_limit ?? 0,
                'canUpload' => $user->canSummarizePdf(),
            ];
        }

        return Inertia::render('welcome', [
            'canRegister' => true,
            'plans' => Plan::query()
                ->where('is_active', true)
                ->orderBy('price')
                ->get()
                ->map(fn (Plan $plan): array => [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'description' => $plan->description,
                    'price' => (float) $plan->price,
                    'pdf_limit' => $plan->pdf_limit,
                    'features' => is_array($plan->features) ? $plan->features : [],
                ])
                ->values()
                ->all(),
            'currentPlanSlug' => $user?->plan?->slug,
            'auth' => [
                'user' => $user,
            ],
            'userStats' => $userStats,
        ]);
    }

    public function store(StoreSubscriptionRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = Auth::user();
        $plan = Plan::query()->findOrFail($validated['plan_id']);

        try {
            $service = $this->makeSubscriptionService(
                $validated['gateway'] ?? config('payment.default_gateway')
            );

            return redirect()->away($service->subscribe($user, $plan));
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
        $newPlan = Plan::query()->findOrFail($validated['new_plan_id']);

        try {
            $service = $this->makeSubscriptionService(
                $validated['gateway'] ?? config('payment.default_gateway')
            );

            $service->changePlan($user, $newPlan);

            return redirect()->with('success', 'Plan updated successfully.');
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
        $plan = $user->plan;

        try {
            $service = $this->makeSubscriptionService(
                $validated['gateway'] ?? config('payment.default_gateway')
            );

            $service->cancel($user);

            return redirect()->with('success', 'Unsubscribed successfully.');
        } catch (Throwable $th) {
            Log::error('Error canceling subscription', [
                'message' => $th->getMessage(),
                'code' => $th->getCode(),
                'line' => $th->getLine(),
                'file' => $th->getFile(),
                'trace' => $th->getTraceAsString(),
                'user_id' => $user->id,
                'plan_id' => $plan->id,
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
