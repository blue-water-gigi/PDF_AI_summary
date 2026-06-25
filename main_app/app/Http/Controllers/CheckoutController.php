<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Plan;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Plan $plan): Response
    {
        return Inertia::render('Checkout/Index', [
            'plan' => $plan,
            'gateways' => config('payment.available_gateways'),
        ]);
    }
}
