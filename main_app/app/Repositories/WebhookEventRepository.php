<?php

namespace App\Repositories;

use App\Models\WebhookEvent;
use Illuminate\Database\UniqueConstraintViolationException;

class WebhookEventRepository
{

    /**
     *  Retrieve event by its platform, id, type and payload.
     *  If not - create one.
     *  Default status = 'processing'
     *
     * @param  string  $platform
     * @param  string  $eventId
     * @param  string  $eventType
     * @param  array  $payload
     * @return WebhookEvent
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
                'payload' => $payload
            ]);
        } catch (UniqueConstraintViolationException $e) {
            return WebhookEvent::query()
                ->where('event_id', $eventId)
                ->where('platform', $platform)
                ->firstOrFail();
        }
    }
}
