<?php

namespace App\DTO;

readonly class Webhook
{


    /**
     * @param  array  $payload
     * @param  string  $platform
     * @param  string  $rawBody
     * @param  array  $headers
     */
    public function __construct(
        private array $payload,
        private string $platform,
        private string $rawBody,
        private array $headers,
    ) {
    }

    public function getHeaders(): array
    {
        return $this->headers;
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

    public function getSignature(string $headerKey): ?string
    {
        $key = strtolower($headerKey);
        $value = $this->headers[$key] ?? null;
        return is_array($value) ? ($value[0] ?? null) : $value;
    }
}
