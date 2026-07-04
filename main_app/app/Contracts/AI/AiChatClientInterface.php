<?php

namespace App\Contracts\AI;

interface AiChatClientInterface
{
    /**
     * Send the payload to the API of model provider
     *
     * @param array<int, array{role: string, content: string}> $messages
     * @param string $modelType key identifying which model tier to use
     */
    public function sendAndReceive(array $messages, string $modelType = 'free'): mixed;
}
