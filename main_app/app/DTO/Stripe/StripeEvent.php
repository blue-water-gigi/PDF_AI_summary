<?php

namespace App\DTO\Stripe;

readonly class StripeEvent
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        private string $objectId,
        private string $type,
        private array $data,
        private array $metadata,
    ) {
        //
    }

    public function getObjectId(): string
    {
        return $this->objectId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
