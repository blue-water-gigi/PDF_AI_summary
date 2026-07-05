<?php

namespace App\Services\AI;

use App\Contracts\AI\AiChatClientInterface;
use App\Exceptions\Ai\AiClientException;
use App\Exceptions\Ai\ClientRequestException;
use App\Exceptions\Ai\ClientResponseException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenRouterClient implements AiChatClientInterface
{
    private string $endpoint = 'https://openrouter.ai/api/v1/chat/completions';
    private array $models;

    /**
     * Create a new class instance.
     */
    public function __construct(
        private readonly ?string $apiKey = null,
        private readonly string  $appUrl,
        private readonly string  $appName,
    )
    {
        $this->models = config('models');
    }

    /**
     * @param array<string,mixed> $messages
     * @param string $modelType key from models config file. 'free' as default
     * @return mixed
     * @throws ClientRequestException
     * @throws ClientResponseException
     * @throws ConnectionException
     * @throws AiClientException
     */
    public function sendAndReceive(array $messages, string $modelType = 'free'): mixed
    {
        $this->apiKeyCheck();

        $request = Http::timeout(120)->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'HTTP-Reffer' => $this->appUrl,
            'X-OpenRouter-Title' => $this->appName,
            'content-type' => 'application/json',
        ]);

        try {
            $response = $request->post($this->endpoint, [
                'models' => $this->models[strtolower($modelType)],
                'messages' => $messages,
            ]);

            if ($response->failed()) {
                Log::error('OpenRouter API Error,', [
                    'errorBody' => $response->body(),
                    'status' => $response->status(),
                ]);

                $message = $response->json('error.message') ?? 'Failed to connect to OpenRouter API';

                throw new ClientRequestException(
                    'OpenRouter',
                    $message,
                    $response->status()
                );
            }

            $data = $response->json();

            if (!isset($data['choices'][0]['message']['content'])) {
                Log::error('Unexpected API response structure.', [
                    'data' => $data
                ]);

                throw new ClientResponseException(
                    'OpenRouter',
                    'Unexpected API response structure.',
                    $response->status()
                );
            }

            return $data['choices'][0]['message']['content'];
        } catch (ClientRequestException|ClientResponseException $e) {
            throw $e;
        } catch (\Throwable $th) {
            Log::error('OpenRouter API Error,', [
                'message' => $th->getMessage(),
            ]);

            throw new AiClientException(
                'OpenRouter',
                'OpenRouter API Error',
                500,
                $th
            );
        }
    }

    /**
     * @throws ClientRequestException
     */
    private function apiKeyCheck(): void
    {
        if (empty($this->apiKey)) {
            Log::error('OpenRouter API key is not set.');

            throw new ClientRequestException(
                'OpenRouter',
                'OpenRouter API key is not set.',
                500
            );
        }
    }
}
