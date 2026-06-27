<?php

declare(strict_types=1);

namespace App\DTO;

readonly class Webhook
{
    public function __construct(
        private array $payload,
        private string $platform,
        private string $rawBody,
        private array $headers,
    ) {}

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
        // Header names may be provided with different casing by the HTTP layer
        // (e.g. Symfony returns lowercase keys). Perform a case-insensitive
        // lookup to be robust in both unit tests and real HTTP requests.
        foreach ($this->headers as $key => $value) {
            if (strtolower((string) $key) === strtolower($headerKey)) {
                $signature = is_array($value) ? ($value[0] ?? null) : $value;

                if ($signature !== null) {
                    return $signature;
                }
            }
        }

        return null;
    }
}
