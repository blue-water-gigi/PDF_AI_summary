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

    Route::post('/pdf/summarize', [PdfController::class, 'summarize'])->name('pdf.summarize');
    Route::get('/dashboard/history', [PdfController::class, 'index'])->name('dashboard.index');

    Route::get('users', [AdminController::class, 'index'])->name('admin.index');
    Route::post('users/{user}/plan', [AdminController::class, 'updateUserPlan'])->name('admin.users.update-plan');
});

Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::redirect('dashboard/users', '/users');
});

Route::post('/webhook/{platform}', WebhookController::class)
    ->name('webhook')
    ->whereIn('platform', config('payment.available_gateways'))
    ->withoutMiddleware([VerifyCsrfToken::class]);


require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
