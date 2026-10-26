<?php

return [
    'enabled' => (bool) env('MIKROTIK_ENABLED', false),
    'base_url' => env('MIKROTIK_BASE_URL'),
    'username' => env('MIKROTIK_USERNAME'),
    'password' => env('MIKROTIK_PASSWORD'),
    'ca_bundle' => env('MIKROTIK_CA_BUNDLE'),
];
