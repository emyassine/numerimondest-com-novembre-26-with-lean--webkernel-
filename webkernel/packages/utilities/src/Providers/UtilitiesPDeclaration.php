<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Utilities\Providers;

use Webkernel\ServiceProvider\PackageMetadata;

final class UtilitiesPDeclaration extends PackageMetadata
{
    protected const array METADATA = [
        'prefix' => 'utilities',
        'catalog' => [
            'title' => [
                'fr' => 'Utilitaires',
                'en' => 'Utilities'
            ],
            'summary' => [
                'fr' => 'Utilitaires partagés pour les packages Webkernel.',
                'en' => 'Shared utilities for Webkernel packages.'
            ],
            'category' => [
                'fr' => 'Fondations',
                'en' => 'Foundation'
            ],
            'features' => [
                0 => [
                    'title' => [
                        'fr' => 'Utilitaires PHP partagés',
                        'en' => 'Shared PHP utilities'
                    ]
                ]
            ],
            'visibility' => 'public',
            'type' => 'foundation'
        ]
    ];
}
