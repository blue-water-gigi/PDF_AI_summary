<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\StripeServiceProvider;

return [
    AppServiceProvider::class,
    StripeServiceProvider::class,
];
