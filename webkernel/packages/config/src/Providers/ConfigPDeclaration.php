<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Config\Providers;

use Webkernel\Config\Deployment\ConfigArtifactBuilder;
use Webkernel\ServiceProvider\PackageMetadata;

final class ConfigPDeclaration extends PackageMetadata
{
    protected const array METADATA = [
        'prefix' => 'config',
        'artifacts' => [ConfigArtifactBuilder::class],
        'catalog' => [
            'title' => [
                'fr' => 'Configuration',
                'en' => 'Configuration'
            ],
            'summary' => [
                'fr' => 'Configuration compilée des applications Webkernel.',
                'en' => 'Compiled application configuration for Webkernel.'
            ],
            'category' => [
                'fr' => 'Fondations',
                'en' => 'Foundation'
            ],
            'features' => [
                0 => [
                    'title' => [
                        'fr' => 'Configuration compilée',
                        'en' => 'Compiled configuration'
                    ]
                ],
                1 => [
                    'title' => [
                        'fr' => 'Valeurs adaptées à l’environnement',
                        'en' => 'Environment-aware values'
                    ]
                ]
            ],
            'visibility' => 'public',
            'type' => 'foundation'
        ]
    ];
}
