<?php

namespace App\Notifications;

use App\Models\PdfSummary;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SummaryCreated extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(private readonly PdfSummary $summary)
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
            'title' => 'Summary created',
            'message' => 'Summary created. See your history.',
            'summary_id' => $this->summary->id,
            'filename' => $this->summary->filename,
            'summary_type' => $this->summary->summary_type,
            'summary_created_at' => now()->toDateTimeString(),
        ];
    }
}