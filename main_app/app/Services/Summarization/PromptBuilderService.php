<?php

namespace App\Services\Summarization;

class PromptBuilderService
{
    private const string DEFAULT_TYPE = 'standard';

    /**
     * Build system prompt.
     * Intentionally set to public due to flexibility of building a request with certain structure for other APIs
     */
    public function buildSystemPrompt(): string
    {
        return config('prompt')['system'];
    }

    /**
     * Build user prompt. Intentionally set to public due to flexibility
     * of building a request with certain structure for other APIs
     *
     */
    public function buildUserPrompt(string $summaryType): string
    {
        return config("prompt.{$summaryType}", config('prompt.' . self::DEFAULT_TYPE));
    }

    public function build(string $summaryType, string $text): array
    {
        return [
            [
                'role' => 'system',
                'content' => $this->buildSystemPrompt(),
            ],
            [
                'role' => 'user',
                'content' => $this->buildUserPrompt($summaryType),
            ],
            [
                'role' => 'user',
                'content' => $text,
            ]
        ];
    }
}
