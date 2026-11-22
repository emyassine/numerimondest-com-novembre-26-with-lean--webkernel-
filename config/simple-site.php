<?php declare(strict_types=1);

return [
    'sites' => [
        'studio' => [
            'name' => 'Numerimondes',
            'prefix' => '/',
            'canonical_url' => 'https://numerimondes.com',
            'pages' => base_path('app/resources/views/pages'),
            'view_namespace' => 'studio',
            'view_prefix' => 'pages',
            'llm' => base_path('public/llms.txt'),
            'locales' => ['en'],
            'locale' => 'en',
            'fallback_locale' => 'en',
            'feed' => [
                'title' => 'Numerimondes updates',
                'description' => 'Updates to Numerimondes services, projects, and site information.',
                'language' => 'en',
            ],
            'currency_config' => 'nm-studio.currencies',
            'ui' => ['header' => ['contrast' => 'sections']],
            'og_image' => [
                [
                    'url' => '/brand/og-image/revenue-desk.webp',
                    'alt' => 'Numerimondes — business operations and planning',
                    'width' => 1200,
                    'height' => 630,
                ],
                [
                    'url' => '/brand/og-image/sales-operations.webp',
                    'alt' => 'Numerimondes — sales operations and financial review',
                    'width' => 1200,
                    'height' => 630,
                ],
                [
                    'url' => '/brand/og-image/digital-infrastructure.webp',
                    'alt' => 'Numerimondes — digital infrastructure and technology',
                    'width' => 1200,
                    'height' => 630,
                ],
                [
                    'url' => '/brand/og-image/workflow-automation.webp',
                    'alt' => 'Numerimondes — workflow automation and connected systems',
                    'width' => 1200,
                    'height' => 630,
                ],
                [
                    'url' => '/brand/og-image/operations-planning.webp',
                    'alt' => 'Numerimondes — business operations and planning',
                    'width' => 1200,
                    'height' => 630,
                ],
                [
                    'url' => '/brand/og-image/brand-strategy.webp',
                    'alt' => 'Numerimondes — brand strategy and identity',
                    'width' => 1200,
                    'height' => 630,
                ],
                [
                    'url' => '/brand/og-image/business-planning.webp',
                    'alt' => 'Numerimondes — business planning and productivity',
                    'width' => 1200,
                    'height' => 630,
                ],
                [
                    'url' => '/brand/og-image/creative-workspace.webp',
                    'alt' => 'Numerimondes — creative services and planning',
                    'width' => 1200,
                    'height' => 630,
                ],
                [
                    'url' => '/brand/og-image/project-planning.webp',
                    'alt' => 'Numerimondes — project planning and business support',
                    'width' => 1200,
                    'height' => 630,
                ],
                [
                    'url' => '/brand/og-image/digital-workspace.webp',
                    'alt' => 'Numerimondes — digital workspace and online business',
                    'width' => 1200,
                    'height' => 630,
                ],
            ],
        ],
    ],
    'robots_file' => base_path('public/robots.txt'),
    'tdmrep_file' => base_path('public/.well-known/tdmrep.json'),
];
