<?php

declare(strict_types=1);

namespace App\Handlers;

use App\Contracts\WebhookHandler;
use App\DTO\Webhook;

class YoomoneyWebhookHandler implements WebhookHandler
{
    private const string SUPPORTED_PLATFORM = 'yoomoney';

    public function supports(Webhook $webhook): bool
    {
        return $webhook->getPlatform() === self::SUPPORTED_PLATFORM;
    }

    public function handle(Webhook $webhook): void
    {
        dump(self::SUPPORTED_PLATFORM);
    }
}
