<?php declare(strict_types=1);

return [
    'sites' => [
        'studio' => [
            'prefix' => '/',
            'pages' => base_path('app/resources/views/pages'),
            'view_namespace' => 'studio',
            'view_prefix' => 'pages',
            'locales' => ['en'],
            'fallback_locale' => 'en',
            'currency_config' => 'nm-studio.currencies',
            'ui' => ['header' => ['contrast' => 'sections']],
            'og_image' => [
                [
                    'url' => '/brand/numerimondes-logo-lightmode.png',
                    'alt' => 'Numerimondes',
                    'width' => 1305,
                    'height' => 512,
                ],
            ],
        ],
    ],
    'robots_file' => base_path('public/robots.txt'),
    'tdmrep_file' => base_path('public/.well-known/tdmrep.json'),
];
