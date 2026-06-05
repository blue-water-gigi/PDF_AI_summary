<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\PaymentFactoryServiceProvider;

return [
    AppServiceProvider::class,
    PaymentFactoryServiceProvider::class,
];
