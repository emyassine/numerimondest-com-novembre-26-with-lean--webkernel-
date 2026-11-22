<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Container\Providers;

use Webkernel\ServiceProvider\PackageMetadata;

final class ContainerPDeclaration extends PackageMetadata
{
    protected const array METADATA = [
        'prefix' => 'container',
        'catalog' => [
            'title' => [
                'fr' => 'Conteneur de dépendances',
                'en' => 'Dependency Container'
            ],
            'summary' => [
                'fr' => 'Conteneur de dépendances PSR-11 pour Webkernel.',
                'en' => 'PSR-11 dependency container for Webkernel.'
            ],
            'category' => [
                'fr' => 'Fondations',
                'en' => 'Foundation'
            ],
            'features' => [
                0 => [
                    'title' => [
                        'fr' => 'Conteneur PSR-11',
                        'en' => 'PSR-11 container'
                    ]
                ],
                1 => [
                    'title' => [
                        'fr' => 'Services à portée définie',
                        'en' => 'Scoped services'
                    ]
                ]
            ],
            'visibility' => 'public',
            'type' => 'foundation'
        ]
    ];
}
