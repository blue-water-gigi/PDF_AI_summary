<?php

namespace App\Providers;

use App\Contracts\AI\AiChatClientInterface;
use App\Services\AI\OpenRouterClient;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class OpenRouterServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(AiChatClientInterface::class, function (Application $app) {
            return new OpenRouterClient(
                apiKey: config('services.openrouter.key'),
                appUrl: config('app.url'),
                appName: config('app.name'),
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {

    }
}
