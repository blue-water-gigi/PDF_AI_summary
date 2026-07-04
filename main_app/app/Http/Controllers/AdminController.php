<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTO\SubscriptionStatus;
use App\Http\Requests\Admin\UpdateUserPlanRequest;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Throwable;

class AdminController extends Controller
{
    use AuthorizesRequests;

    public function index(): InertiaResponse
    {
        abort_if(!Auth::user()?->isAdmin(), 403);

        $users = User::query()
            ->with(['plan', 'subscription'])
            ->withCount('pdfSummaries')
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $plans = Plan::query()
            ->where('is_active', true)
            ->orderBy('price')
            ->get();

        return Inertia::render('admin/users', [
            'users' => $users,
            'plans' => $plans,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function updateUserPlan(UpdateUserPlanRequest $request, User $user): RedirectResponse
    {
        $plan = Plan::query()->findOrFail($request->validated('plan_id'));
        $periodEnd = $plan->price > 0 ? now()->addMonth() : null;

        DB::transaction(function () use ($user, $plan, $periodEnd) {
            $user->update([
                'plan_id' => $plan->id,
                'pdf_count' => 0,
                'pdf_count_resets_at' => $periodEnd ?? now()->addMonth(),
            ]);

            $subscription = $user->subscription;
            $subscriptionData = [
                'plan_id' => $plan->id,
                'gateway' => $subscription?->gateway ?? 'admin',
                'gateway_customer_id' => $subscription?->gateway_customer_id ?? 'admin_user_' . $user->id,
                'gateway_subscription_id' => $subscription?->gateway_subscription_id ?? 'admin_subscription_' . $user->id,
                'status' => $plan->price > 0 ? SubscriptionStatus::ACTIVE : SubscriptionStatus::CANCELED,
                'current_period_end' => $periodEnd,
                'cancelled_at' => $plan->price > 0 ? null : now(),
                'trial_ends_at' => null,
            ];

            if ($subscription) {
                $subscription->update($subscriptionData);
            } else {
                $user->subscription()->create($subscriptionData);
            }
        });

        return back()->with('success', 'User plan has been updated.');
    }
}
