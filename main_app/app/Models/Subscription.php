<?php

namespace App\Models;

use App\DTO\SubscriptionStatus;
use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

class Subscription extends Model
{
    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_id',
        'gateway',
        'gateway_customer_id',
        'gateway_subscription_id',
        'status',
        'current_period_end',
        'cancelled_at',
        'trial_ends_at',
    ];

    #[Override]
    public function casts(): array
    {
        return [
            'gateway' => 'string',
            'status' => SubscriptionStatus::class,
            'current_period_end' => 'datetime',
            'cancelled_at' => 'datetime',
            'trial_ends_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
