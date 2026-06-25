<?php

declare(strict_types=1);

return [

    /*
        |--------------------------------------------------------------------------
        | Default Gateway
        |--------------------------------------------------------------------------
        |
        | This option controls the default gateway used for payment operations
        | when the frontend does not send a 'gateway' input.
        | If you dont define PAYMENT_GATEWAY explicitly the default
        | gonna be used, so you don't have to worry about setting it up manually.
        |
        */

    'default_gateway' => env('PAYMENT_GATEWAY', 'yoomoney'),

    /*
        |--------------------------------------------------------------------------
        | Available gateways
        |--------------------------------------------------------------------------
        |
        | Define all available gateways for app
        |
        */

    'available_gateways' => [
        'stripe',
        'yoomoney',
    ],
];
