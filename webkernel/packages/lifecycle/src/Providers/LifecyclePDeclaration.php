<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Lifecycle\Providers;

use Webkernel\ServiceProvider\PackageMetadata;

final class LifecyclePDeclaration extends PackageMetadata
{
    protected const array METADATA = [
        'prefix' => 'lifecycle',
        'catalog' => [
            'title' => [
                'fr' => 'Cycle de vie',
                'en' => 'Lifecycle'
            ],
            'summary' => [
                'fr' => 'Plugin Composer, API et moteur d’exécution du cycle de vie Webkernel.',
                'en' => 'Webkernel Lifecycle Composer plugin, API, and execution engine.'
            ],
            'category' => [
                'fr' => 'Cycle de vie Composer',
                'en' => 'Composer lifecycle'
            ],
            'features' => [
                0 => [
                    'title' => [
                        'fr' => 'Événements du cycle Composer',
                        'en' => 'Composer lifecycle hooks'
                    ]
                ],
                1 => [
                    'title' => [
                        'fr' => 'Validation des déclarations',
                        'en' => 'Declaration validation'
                    ]
                ]
            ],
            'visibility' => 'public',
            'branding' => [
                'logo' => 'art/webkernel-lifecycle-logo-lightmode.png',
                'logo_dark' => 'art/webkernel-lifecycle-logo-darkmode.png',
                'favicon' => 'art/webkernel-lifecycle-logo-favicon.png',
                'alt' => [
                    'fr' => 'Logo de Webkernel Lifecycle',
                    'en' => 'Webkernel Lifecycle logo'
                ]
            ],
            'type' => 'foundation'
        ]
    ];
}
