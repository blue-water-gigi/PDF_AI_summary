<?php

namespace App\Listeners;

use App\Events\SubscriptionActivated;
use App\Notifications\SubscriptionActivatedNotification;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;

class SendActivationNotificationListener implements ShouldQueueAfterCommit
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {

    }

    /**
     * Handle the event.
     */
    public function handle(SubscriptionActivated $event): void
    {
        $event->user->notify(new SubscriptionActivatedNotification($event->subscription));
    }
}
