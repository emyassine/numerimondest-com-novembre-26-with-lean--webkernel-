<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\HttpFoundation\Providers;

use Webkernel\ServiceProvider\PackageMetadata;

final class HttpFoundationPDeclaration extends PackageMetadata
{
    protected const array METADATA = [
        'prefix' => 'http_foundation',
        'catalog' => [
            'title' => [
                'fr' => 'Fondation HTTP',
                'en' => 'HTTP Foundation'
            ],
            'summary' => [
                'fr' => 'Intégration Symfony HttpFoundation pour les applications Webkernel.',
                'en' => 'Symfony HttpFoundation dependency for Webkernel.'
            ],
            'category' => [
                'fr' => 'HTTP',
                'en' => 'HTTP'
            ],
            'features' => [
                0 => [
                    'title' => [
                        'fr' => 'Fondation HTTP',
                        'en' => 'HTTP foundation'
                    ]
                ],
                1 => [
                    'title' => [
                        'fr' => 'Intégration Symfony HttpFoundation',
                        'en' => 'Symfony HttpFoundation integration'
                    ]
                ]
            ],
            'visibility' => 'public',
            'type' => 'http'
        ]
    ];
}
