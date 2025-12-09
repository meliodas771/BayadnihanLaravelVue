<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'broadcasting/auth'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:8000',
        'http://127.0.0.1:8000',
        'http://localhost:3000',
        'http://127.0.0.1:3000',
        'http://localhost:3001',
        'http://127.0.0.1:3001',
        'http://localhost:5173', // Vite dev server
        'http://127.0.0.1:5173', // Vite dev server
        'http://192.168.1.2:8000', //  laptop IP
        'http://192.168.1.2:8080', //  Reverb port
    ],

    'allowed_origins_patterns' => [
        '/^http:\/\/localhost:\d+$/', // Allow any localhost port
        '/^http:\/\/127\.0\.0\.1:\d+$/', // Allow any 127.0.0.1 port
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false, // Set to false for API token authentication
];

