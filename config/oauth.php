<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enabled OAuth Providers
    |--------------------------------------------------------------------------
    |
    | This option defines the list of OAuth providers enabled for your application.
    | Add or remove providers as needed. Ensure the necessary credentials are
    | configured in your .env file or the config/services.php file.
    |
    */

    'enabled_providers' => [
        'google' => [
            'name' => 'Google',
            'icon' => 'google',
            'auth_url' => "/auth/google/",
        ],
        'github' => [
            'name' => 'GitHub',
            'icon' => 'github',
            'auth_url' => "/auth/github/",
        ],
    ],
];