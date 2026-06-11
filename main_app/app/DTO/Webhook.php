<?php

namespace App\DTO;

readonly class Webhook
{

    /**
     *
     * @param  array<string,mixed>  $payload
     * @param  string  $platform
     * @param  string  $rawBody
     */
    public function __construct(
        private array $payload,
        private string $platform,
        private string $rawBody,
        private ?string $signature,
    ) {
    }

    public function getSignature(): string
    {
        return $this->signature;
    }

    public function getRawBody(): string
    {
        return $this->rawBody;
    }

    public function getPlatform(): string
    {
        return $this->platform;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }
}
