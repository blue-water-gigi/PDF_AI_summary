<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SubscriptionUpdatedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(private readonly Subscription $subscription)
    {
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

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Subscription renewed',
            'message' => 'Your subscription has been renewed.',
            'plan_id' => $this->subscription->plan_id,
            'gateway' => $this->subscription->gateway,
            'renewed_at' => now()->toDateTimeString(),
        ];
    }
}
