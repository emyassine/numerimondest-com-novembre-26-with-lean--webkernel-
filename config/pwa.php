<?php declare(strict_types=1);

return [
    'enabled' => true,
    'name' => 'Numerimondes',
    'short_name' => 'Numerimondes',
    'description' => 'Numerimondes — Ideas, Strategy, Execution',
    'id' => '/',
    'start_url' => '/',
    'scope' => '/',
    'display' => 'standalone',
    'orientation' => 'any',
    'background_color' => '#f1f5f9',
    'theme_color' => '#f1f5f9',
    'lang' => 'en',
    'icons' => [
        [
            'src' => '/brand/pwa-icon-192.png',
            'sizes' => '192x192',
            'type' => 'image/png',
            'purpose' => 'any',
        ],
    ],
    // These browser APIs require secure context and user permission at the point of use.
    'capabilities' => [
        'geolocation' => true,
        'notifications' => true,
        'camera' => true,
        'microphone' => true,
    ],
];
