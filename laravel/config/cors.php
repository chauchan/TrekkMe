<?php

// La API usa Bearer (no cookies), así que no se permiten credenciales.
// Orígenes de Ionic: navegador (ionic serve) y WebView de Capacitor (iOS y Android).
return [
    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_filter(explode(',', env(
        'CORS_ALLOWED_ORIGINS',
        'http://localhost:8100,http://localhost,https://localhost,capacitor://localhost,ionic://localhost'
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Authorization', 'Content-Type', 'Accept', 'X-Requested-With'],

    'exposed_headers' => [],

    'max_age' => 600,

    'supports_credentials' => false,
];
