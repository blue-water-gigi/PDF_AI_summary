<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SubscriptionActivatedNotification extends Notification
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
            'title' => 'Subscription Activated',
            'message' => 'Your subscription has been activated.',
            'plan_id' => $this->subscription->plan_id,
            'gateway' => $this->subscription->gateway,
            'activated_at' => $this->subscription->current_period_end->toDateTimeString(),
        ];
    }
}
