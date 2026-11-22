<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Console\Providers;

use Webkernel\ServiceProvider\PackageMetadata;

final class ConsolePDeclaration extends PackageMetadata
{
    protected const array METADATA = [
        'prefix' => 'console',
        'catalog' => [
            'title' => [
                'fr' => 'Console',
                'en' => 'Console'
            ],
            'summary' => [
                'fr' => 'Analyse des commandes, distribution et affichage dans le terminal.',
                'en' => 'Command line input, command dispatch, and terminal output for Webkernel.'
            ],
            'category' => [
                'fr' => 'Ligne de commande',
                'en' => 'Command line'
            ],
            'features' => [
                0 => [
                    'title' => [
                        'fr' => 'Analyse des arguments CLI',
                        'en' => 'CLI input parsing'
                    ]
                ],
                1 => [
                    'title' => [
                        'fr' => 'Exécution des commandes',
                        'en' => 'Command dispatch'
                    ]
                ]
            ],
            'visibility' => 'public',
            'type' => 'runtime'
        ]
    ];
}
