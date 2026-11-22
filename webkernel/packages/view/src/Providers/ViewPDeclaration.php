<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\View\Providers;

use Webkernel\View\Deployment\ViewArtifactBuilder;
use Webkernel\ServiceProvider\PackageMetadata;

final class ViewPDeclaration extends PackageMetadata
{
    protected const array METADATA = [
        'prefix' => 'view',
        'artifacts' => [ViewArtifactBuilder::class],
        'catalog' => [
            'title' => [
                'fr' => 'Vues',
                'en' => 'Views'
            ],
            'summary' => [
                'fr' => 'Résolution et rendu des vues PHP pour Webkernel.',
                'en' => 'PHP view resolution and rendering for Webkernel.'
            ],
            'category' => [
                'fr' => 'Frontend',
                'en' => 'Frontend'
            ],
            'features' => [
                0 => [
                    'title' => [
                        'fr' => 'Templates rendus côté serveur',
                        'en' => 'Server-rendered templates'
                    ]
                ],
                1 => [
                    'title' => [
                        'fr' => 'Sorties échappées et composants',
                        'en' => 'Escaped output and components'
                    ]
                ]
            ],
            'visibility' => 'public',
            'type' => 'frontend'
        ]
    ];
}
