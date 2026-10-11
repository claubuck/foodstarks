<?php

return [

    'default' => env('BROADCAST_CONNECTION', 'null'),

    'connections' => [

        'reverb' => [
            'driver' => 'reverb',
            'key' => env('REVERB_APP_KEY', 'foodstarks-key'),
            'secret' => env('REVERB_APP_SECRET', 'foodstarks-secret'),
            'app_id' => env('REVERB_APP_ID', 'foodstarks'),
            'options' => [
                // Servidor: en Docker usar "reverb", en local usar REVERB_HOST.
                'host' => env('REDIS_HOST') === 'redis' ? 'reverb' : env('REVERB_HOST', 'localhost'),
                'port' => (int) env('REVERB_PORT', 8080),
                'scheme' => env('REVERB_SCHEME', 'http'),
                'useTLS' => env('REVERB_SCHEME', 'http') === 'https',
            ],
            // URL que debe usar el navegador (siempre localhost o dominio público, nunca "reverb").
            'public_host' => env('REVERB_PUBLIC_HOST', env('REVERB_HOST', 'localhost')),
            'public_port' => (int) env('REVERB_PUBLIC_PORT', env('REVERB_PORT', 8080)),
            'public_scheme' => env('REVERB_PUBLIC_SCHEME', env('REVERB_SCHEME', 'http')),
            'client_options' => [
            ],
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

];
