<?php

namespace App\Contracts;

use App\DTO\Webhook;

interface WebhookHandler
{
    public function supports(Webhook $webhook): bool;

    public function handle(Webhook $webhook): void;
}
