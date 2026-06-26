<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentFailedNotification extends Notification
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
     * Get the mail representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Payment failed',
            'message' => 'Payment failed! Please try again.',
            'plan_id' => $this->subscription->plan_id,
            'gateway' => $this->subscription->gateway,
            'activated_at' => now()->toDateTimeString(),
        ];
    }
}
