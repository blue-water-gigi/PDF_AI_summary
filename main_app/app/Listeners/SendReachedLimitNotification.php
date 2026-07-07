<?php

namespace App\Listeners;

use App\Events\LimitReached;
use App\Notifications\LimitReached as LimitReachedNotification;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;

class SendReachedLimitNotification implements ShouldQueueAfterCommit
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
    public function handle(LimitReached $event): void
    {
        $event->user->notify(new LimitReachedNotification($event->subscription ?? null));
    }
}
