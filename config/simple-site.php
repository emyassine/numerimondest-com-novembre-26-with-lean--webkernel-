<?php declare(strict_types=1);

return [
    'sites' => [
        'studio' => [
            'prefix' => '/',
            'pages' => base_path('app/resources/views/pages'),
            'view_namespace' => 'studio',
            'view_prefix' => 'pages',
            'llm' => base_path('public/llms.txt'),
            'locales' => ['en'],
            'fallback_locale' => 'en',
            'currency_config' => 'nm-studio.currencies',
            'ui' => ['header' => ['contrast' => 'sections']],
            'og_image' => [
                [
                    'url' => '/brand/og-image/silk/numerimondes-og.webp',
                    'alt' => 'Numerimondes — vivid blue fabric backdrop',
                    'width' => 1200,
                    'height' => 630,
                ],
                [
                    'url' => '/brand/og-image/brand-digital/numerimondes-og.webp',
                    'alt' => 'Numerimondes — brand identity and digital presence',
                    'width' => 1200,
                    'height' => 630,
                ],
                [
                    'url' => '/brand/og-image/revenue-desk/numerimondes-og.webp',
                    'alt' => 'Numerimondes — business operations and planning',
                    'width' => 1200,
                    'height' => 630,
                ],
                [
                    'url' => '/brand/og-image/business-website/numerimondes-og.webp',
                    'alt' => 'Numerimondes — business websites and digital presence',
                    'width' => 1200,
                    'height' => 630,
                ],
                [
                    'url' => '/brand/og-image/talent-search/numerimondes-og.webp',
                    'alt' => 'Numerimondes — remote talent search',
                    'width' => 1200,
                    'height' => 630,
                ],
            ],
        ],
    ],
    'robots_file' => base_path('public/robots.txt'),
    'tdmrep_file' => base_path('public/.well-known/tdmrep.json'),
];
