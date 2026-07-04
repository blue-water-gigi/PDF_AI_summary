<?php

namespace App\DTO;

use Illuminate\Contracts\Support\Arrayable;

final readonly class SummaryResult implements Arrayable
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public int|string   $id,
        public string|array $summary,
    )
    {
    }

    /**
     * @return int|string
     */
    public function getId(): int|string
    {
        return $this->id;
    }

    /**
     * @return array|string
     */
    public function getSummary(): array|string
    {
        return $this->summary;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'summary' => $this->summary,
        ];
    }
}
