<?php

declare(strict_types=1);

use App\Http\Controllers\AdminController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\WebhookController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/', [SubscriptionController::class, 'index'])->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', [SubscriptionController::class, 'dashboard'])
        ->name('dashboard');
    Route::post('/subscription', [SubscriptionController::class, 'store'])
        ->name('subscription.store');
    Route::delete('/subscription', [SubscriptionController::class, 'destroy'])
        ->name('subscription.destroy');
    Route::patch('/subscription', [SubscriptionController::class, 'update'])
        ->name('subscription.update');
});

Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('dashboard', [AdminController::class, 'users'])->name('admin.users');
    Route::post('dashboard/users/{user}/plan', [AdminController::class, 'updateUserPlan'])->name('admin.users.update-plan');
});

Route::post('/webhook/{platform}', WebhookController::class)
    ->name('webhook')
    ->whereIn('platform', config('payment.available_gateways'))
    ->withoutMiddleware([VerifyCsrfToken::class]);

Route::post('/pdf/summarize', [PdfController::class, 'summarize'])->name('summarize');

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
