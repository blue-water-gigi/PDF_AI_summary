<?php

namespace App\Listeners;

use App\Events\SummaryCreated;
use App\Notifications\SummaryCreated as SummaryCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;

class SendSummaryCreatedNotification implements ShouldQueueAfterCommit
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
    public function handle(SummaryCreated $event): void
    {
        $event->user->notify(new SummaryCreatedNotification($event->pdfSummary));
    }
}
