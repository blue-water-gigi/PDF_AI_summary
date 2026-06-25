<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\Webhook;

interface WebhookHandler
{
    public function supports(Webhook $webhook): bool;

    public function handle(Webhook $webhook): void;
}
