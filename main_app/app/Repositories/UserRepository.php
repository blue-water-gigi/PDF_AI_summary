<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    public function findById(int $userId): User
    {
        return User::query()->findOrFail($userId);
    }

    public function findByGatewayCustomerId(string $customerId): User
    {
        return User::query()
            ->whereHas('subscription', function ($query) use ($customerId) {
                $query->where('gateway_customer_id', $customerId);
            })
            ->firstOrFail();
    }
}
