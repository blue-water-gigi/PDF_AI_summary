<?php

namespace App\Listeners;

use App\Events\SubscriptionUpdated;
use App\Notifications\SubscriptionUpdatedNotification;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;

class SendUpdateNotificationListener implements ShouldQueueAfterCommit
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
    public function handle(SubscriptionUpdated $event): void
    {
        $event->user->notify(new SubscriptionUpdatedNotification($event->subscription));
    }
}
