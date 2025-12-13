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
        'https://bayadnihan-laravel-vue.vercel.app',
        // Ngrok URLs (all subdomains)
        'https://*.ngrok.io',

        
    ],

    'allowed_origins_patterns' => [
        '/^http:\/\/localhost:\d+$/', // Allow any localhost port
        '/^http:\/\/127\.0\.0\.1:\d+$/', // Allow any 127.0.0.1 port
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['Authorization'],

    'max_age' => 3600, // Cache preflight for 1 hour

    'supports_credentials' => true, // Changed to true for proper token handling
];

