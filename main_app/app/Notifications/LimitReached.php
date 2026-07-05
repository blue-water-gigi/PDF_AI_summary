<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LimitReached extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(private readonly Subscription $subscription)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Limit reached',
            'message' => 'PDF limit reached. Please upgrade your current plan.',
            'plan_id' => $this->subscription?->plan_id ?? 1,
            'gateway' => $this->subscription?->gateway ?? null,
            'limit_reached_at' => now()->toDateTimeString(),
        ];
    }
}
