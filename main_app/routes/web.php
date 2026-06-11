<?php

declare(strict_types=1);

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\WebhookController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [SubscriptionController::class, 'index'])->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', fn() => Inertia::render('dashboard'))
        ->name('dashboard');
    Route::post('/subscription', [SubscriptionController::class, 'store'])
        ->name('subscription.store');
    Route::delete('/subscription', [SubscriptionController::class, 'destroy'])
        ->name('subscription.destroy');
    Route::patch('/subscription', [SubscriptionController::class, 'update'])
        ->name('subscription.update');
    Route::get('/checkout/{plan:slug}', CheckoutController::class)
        ->name('checkout.show');
});
Route::post('/webhook/{platform}', WebhookController::class)
    ->name('webhook')
    ->whereIn('platform', config('payment.available_gateways'))
    ->withoutMiddleware([VerifyCsrfToken::class]);

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
