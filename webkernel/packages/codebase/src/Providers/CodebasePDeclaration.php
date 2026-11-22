<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Codebase\Providers;

use Webkernel\Codebase\Deployment\PackagePublicAssetsBuilder;
use Webkernel\ServiceProvider\PackageMetadata;

final class CodebasePDeclaration extends PackageMetadata
{
    protected const array METADATA = [
        'prefix' => 'webkernel',
        'artifacts' => [PackagePublicAssetsBuilder::class],
        'catalog' => [
            'title' => [
                'fr' => 'Codebase',
                'en' => 'Codebase'
            ],
            'summary' => [
                'fr' => 'Démarrage de l’application et intégration runtime de Webkernel.',
                'en' => 'Webkernel application bootstrap and runtime integration.'
            ],
            'category' => [
                'fr' => 'Plateforme',
                'en' => 'Platform'
            ],
            'features' => [
                0 => [
                    'title' => [
                        'fr' => 'Démarrage de l’application',
                        'en' => 'Application bootstrap'
                    ]
                ],
                1 => [
                    'title' => [
                        'fr' => 'Manifestes de runtime compilés',
                        'en' => 'Compiled runtime manifests'
                    ]
                ]
            ],
            'visibility' => 'public',
            'type' => 'platform'
        ]
    ];
}
