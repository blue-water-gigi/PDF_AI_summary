<?php

namespace App\Listeners;

use App\Events\SubscriptionCancelled;
use App\Notifications\SubscriptionCancelledNotification;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;

class SendCancellationNotificationListener implements ShouldQueueAfterCommit
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }


    /**
     * Handle the event.
     */
    public function handle(SubscriptionCancelled $event): void
    {
        $event->user->notify(new SubscriptionCancelledNotification($event->subscription));
    }
}
