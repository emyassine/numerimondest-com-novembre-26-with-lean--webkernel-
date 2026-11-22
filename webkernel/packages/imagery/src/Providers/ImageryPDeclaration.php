<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Imagery\Providers;

use Webkernel\ServiceProvider\PackageMetadata;

final class ImageryPDeclaration extends PackageMetadata
{
    protected const array METADATA = [
        'prefix' => 'imagery',
        'catalog' => [
            'title' => [
                'fr' => 'Imagerie',
                'en' => 'Imagery'
            ],
            'summary' => [
                'fr' => 'Icônes, marques et images pour Webkernel.',
                'en' => 'Webkernel imagery: icons, brands, pixmaps.'
            ],
            'category' => [
                'fr' => 'Frontend',
                'en' => 'Frontend'
            ],
            'features' => [
                0 => [
                    'title' => [
                        'fr' => 'Collections d’icônes',
                        'en' => 'Icon sets'
                    ]
                ],
                1 => [
                    'title' => [
                        'fr' => 'Images et identités visuelles',
                        'en' => 'Images and brand assets'
                    ]
                ]
            ],
            'visibility' => 'public',
            'type' => 'frontend'
        ]
    ];
}
