<?php

declare(strict_types=1);

use App\Models\Plan;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
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
            ->map(fn(Plan $plan): array => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'description' => $plan->description,
                'price' => (float)$plan->price,
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
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', fn() => Inertia::render('dashboard'))->name('dashboard');
});

Route::get('/checkout/{slug}', function (string $slug) {
    $plan = Plan::query()->where('slug', $slug)->firstOrFail();

    return Inertia::render('checkout', [
        'plan' => $plan,
        'stripeKey' => config('services.stripe.key'),
    ]);
})->name('checkout');

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
