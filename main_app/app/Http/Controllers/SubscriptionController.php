<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Subscription\SubscribeRequest;
use App\Models\User;
use App\Services\Payment\PaymentGatewayFactory;
use App\Services\SubscriptionService;
use Illuminate\Http\Response;

class SubscriptionController extends Controller
{
    public function store(
        SubscribeRequest      $request,
        User                  $user,
        PaymentGatewayFactory $factory,
        SubscriptionService   $service): Response
    {
        $validatedData = $request->validated();

        $factory->resolve('stripe');
    }
}
