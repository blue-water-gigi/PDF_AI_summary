<?php

declare(strict_types=1);

namespace App\Mappers;

use App\DTO\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;

readonly class SubscriptionViewMapper
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function plansForFrontend(): array
    {
        return Plan::query()
            ->where('is_active', true)
            ->orderBy('price')
            ->get()
            ->map(fn(Plan $plan): array => $this->planForFrontend($plan))
            ->values()
            ->all();
    }

    /**
     * @return array{plans: array<int, array<string, mixed>>, currentPlanSlug: string|null, userStats: array{pdfCount: int, pdfLimit: int, canUpload: bool}}
     */
    public function dashboardProps(User $user): array
    {
        $user->loadMissing('plan');

        return [
            'plans' => $this->plansForFrontend(),
            'currentPlanSlug' => $user->plan?->slug,
            'userStats' => $this->userStats($user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function settingsProps(User $user): array
    {
        $user->loadMissing(['plan', 'subscription']);

        return [
            ...$this->sharedProps($user),
            'currentPlan' => $this->planForFrontend($user->plan),
            'subscription' => $this->subscriptionForFrontend($user->subscription),
        ];
    }

    /**
     * @return array{plans: array<int, array<string, mixed>>, currentPlanSlug: string|null, userStats: array{pdfCount: int, pdfLimit: int, canUpload: bool}, hasActiveSubscription: bool}
     */
    public function sharedProps(User $user): array
    {
        $user->loadMissing(['plan', 'subscription']);

        return [
            'plans' => $this->plansForFrontend(),
            'currentPlanSlug' => $user->plan?->slug,
            'userStats' => $this->userStats($user),
            'hasActiveSubscription' => $this->hasActiveSubscription($user->subscription),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function planForFrontend(?Plan $plan): ?array
    {
        if (!$plan instanceof Plan) {
            return null;
        }

        return [
            'id' => $plan->id,
            'name' => $plan->name,
            'slug' => $plan->slug,
            'description' => $plan->description,
            'price' => (float)$plan->price,
            'pdf_limit' => $plan->pdf_limit,
            'features' => is_array($plan->features) ? $plan->features : [],
        ];
    }

    /**
     * @return array{pdfCount: int, pdfLimit: int, canUpload: bool}
     */
    private function userStats(User $user): array
    {
        return [
            'pdfCount' => $user->pdf_count,
            'pdfLimit' => $user->plan?->pdf_limit ?? 0,
            'canUpload' => $user->canSummarizePdf(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function subscriptionForFrontend(?Subscription $subscription): ?array
    {
        if (!$subscription instanceof Subscription) {
            return null;
        }

        return [
            'gateway' => $subscription->gateway,
            'status' => $subscription->status instanceof SubscriptionStatus ? $subscription->status->value : (string)$subscription->status,
            'currentPeriodEnd' => $subscription->current_period_end?->toDateString(),
            'cancelledAt' => $subscription->cancelled_at?->toDateString(),
            'trialEndsAt' => $subscription->trial_ends_at?->toDateString(),
            'isActive' => $this->hasActiveSubscription($subscription),
        ];
    }

    private function hasActiveSubscription(?Subscription $subscription): bool
    {
        return $subscription?->status === SubscriptionStatus::ACTIVE
            && $subscription?->current_period_end?->isFuture() === true;
    }
}
