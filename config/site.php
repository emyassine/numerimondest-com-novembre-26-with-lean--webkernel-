<?php declare(strict_types=1);

return [
    'routes' => [
        'app/routes.php',
    ],
    'views' => [
        'studio' => ['app/resources/views'],
    ],
    'components' => [
        'studio' => ['app/resources/components'],
    ],
    'public_assets' => [
        'numerimondes/website' => [
            'app/resources/css',
            'app/resources/js',
            'app/resources/assets',
            'app/resources/components',
        ],
    ],
];
