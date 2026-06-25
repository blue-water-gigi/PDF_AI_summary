<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\WebhookEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Override;

class WebhookEvent extends Model
{
    /** @use HasFactory<WebhookEventFactory> */
    use HasFactory;

    protected $fillable = [
        'event_id',
        'platform',
        'event_type',
        'status',
        'payload',
        'error',
        'attempts',
        'processed_at',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'payload' => 'json',
            'processed_at' => 'datetime',
        ];
    }

    public function isProcessed(): bool
    {
        return $this->status === 'processed';
    }

    public function markAsProcessed(): void
    {
        $this->update([
            'status' => 'processed',
            'processed_at' => now(),
        ]);
    }

    public function markAsFailed(?string $error = null): void
    {
        $this->increment('attempts', 1, [
            'status' => 'failed',
            'error' => $error,
            'processed_at' => now(),
        ]);
    }
}
