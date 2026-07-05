<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTO\SubscriptionStatus;
use App\Http\Requests\Admin\UpdateUserPlanRequest;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Throwable;

class AdminController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): InertiaResponse
    {
        abort_if(! Auth::user()?->isAdmin(), 403);

        $allowedSorts = [
            'id' => 'id',
            'role' => 'role',
            'plan_id' => 'plan_id',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
            'email_verified_at' => 'email_verified_at',
            'pdf_count_resets_at' => 'pdf_count_resets_at',
        ];

        $sort = (string) $request->query('sort', '');
        $hasActiveSort = array_key_exists($sort, $allowedSorts);
        $requestedDirection = strtolower((string) $request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $direction = $hasActiveSort ? $requestedDirection : 'desc';
        $search = trim((string) $request->query('search', ''));
        $sortColumn = $hasActiveSort ? $allowedSorts[$sort] : 'created_at';

        $users = User::query()
            ->with(['plan', 'subscription'])
            ->withCount('pdfSummaries')
            ->when($search !== '', function ($query) use ($search) {
                $query->whereRaw('LOWER(email) LIKE ?', ['%'.strtolower($search).'%']);
            })
            ->orderBy($sortColumn, $direction)
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        $plans = Plan::query()
            ->where('is_active', true)
            ->orderBy('price')
            ->get();

        return Inertia::render('admin/users', [
            'users' => $users,
            'plans' => $plans,
            'filters' => [
                'search' => $search,
                'sort' => $hasActiveSort ? $sort : null,
                'direction' => $direction,
            ],
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
