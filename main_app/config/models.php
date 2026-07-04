<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Free models
    |--------------------------------------------------------------------------
    |
    | This option controls the configuration that holds all FREE models for application.
    | if you want to provide more specify the models in 'free' array.
    | Examples are provided.
    |
    */

    'free' => [
        "google/gemma-4-31b-it",
        "openai/gpt-oss-20b"
    ],

    /*
    |--------------------------------------------------------------------------
    | Paid models
    |--------------------------------------------------------------------------
    |
    | This option controls the configuration that holds all PAID models for application.
    | if you want to provide more specify the models in 'paid' array.
    | Them tokens gonna burn so be carefully.
    |
    */

    'paid' => []
];
