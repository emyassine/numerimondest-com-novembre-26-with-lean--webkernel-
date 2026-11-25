<?php declare(strict_types=1);

return [
    'view' => 'studio::errors.show',
    'statuses' => [
        403 => [
            'title' => ['en' => 'Access unavailable'],
            'description' => ['en' => 'This page is not available from this connection.'],
        ],
        404 => [
            'title' => ['en' => 'Page not found'],
            'description' => ['en' => 'The page you are looking for does not exist or has been moved.'],
        ],
        500 => [
            'title' => ['en' => 'Server error'],
            'description' => ['en' => 'Something went wrong on our end. Please try again shortly.'],
        ],
    ],
];
