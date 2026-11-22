<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Route\Providers;

use Webkernel\Route\Deployment\RouteArtifactBuilder;
use Webkernel\ServiceProvider\PackageMetadata;

final class RoutePDeclaration extends PackageMetadata
{
    protected const array METADATA = [
        'prefix' => 'route',
        'artifacts' => [RouteArtifactBuilder::class],
        'catalog' => [
            'title' => [
                'fr' => 'Routage',
                'en' => 'Routing'
            ],
            'summary' => [
                'fr' => 'Routage HTTP pour Webkernel.',
                'en' => 'Webkernel routing.'
            ],
            'category' => [
                'fr' => 'HTTP',
                'en' => 'HTTP'
            ],
            'features' => [
                0 => [
                    'title' => [
                        'fr' => 'Déclaration des routes',
                        'en' => 'Route registration'
                    ]
                ],
                1 => [
                    'title' => [
                        'fr' => 'Distribution des requêtes',
                        'en' => 'Request dispatch'
                    ]
                ]
            ],
            'visibility' => 'public',
            'type' => 'http'
        ]
    ];
}
