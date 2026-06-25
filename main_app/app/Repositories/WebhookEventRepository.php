<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\WebhookEvent;
use Illuminate\Database\UniqueConstraintViolationException;

class WebhookEventRepository
{
    /**
     *  Retrieve event by its platform, id, type and payload.
     *  If not - create one.
     *  Default status = 'processing'
     */
    public function findOrCreateByEventId(
        string $platform,
        string $eventId,
        string $eventType,
        array $payload
    ): WebhookEvent {
        try {
            return WebhookEvent::query()->create([
                'platform' => $platform,
                'event_id' => $eventId,
                'event_type' => $eventType,
                'status' => 'processing',
                'payload' => $payload,
            ]);
        } catch (UniqueConstraintViolationException) {
            return WebhookEvent::query()
                ->where('event_id', $eventId)
                ->where('platform', $platform)
                ->firstOrFail();
        }
    }
}
