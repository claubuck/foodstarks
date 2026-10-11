<?php

/*
 * Reverb espera cada app con "key" y "secret" (no app_key/app_secret).
 * Ver: https://github.com/laravel/reverb/blob/main/config/reverb.php
 * y ConfigApplicationProvider (busca por 'key' y crea Application con $app['key']).
 */
$applications = [
    [
        'app_id' => env('REVERB_APP_ID', 'foodstarks'),
        'key' => env('REVERB_APP_KEY', 'foodstarks-key'),
        'secret' => env('REVERB_APP_SECRET', 'foodstarks-secret'),
        'allowed_origins' => explode(',', env('REVERB_ALLOWED_ORIGINS', '*')),
        'ping_interval' => 30,
        'activity_timeout' => 30,
        'max_message_size' => 10_000,
        'options' => [],
    ],
];

return [

    'apps' => [
        'provider' => 'config',
        'apps' => $applications,
    ],

];
