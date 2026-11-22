<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Commands\Providers;

use Webkernel\ServiceProvider\PackageMetadata;

final class CommandsPDeclaration extends PackageMetadata
{
    protected const array METADATA = [
        'prefix' => 'commands',
        'catalog' => [
            'title' => [
                'fr' => 'Commandes console',
                'en' => 'Console Commands'
            ],
            'summary' => [
                'fr' => 'Commandes intégrées pour la console Webkernel.',
                'en' => 'Built-in Webkernel console commands.'
            ],
            'category' => [
                'fr' => 'Expérience développeur',
                'en' => 'Developer experience'
            ],
            'features' => [
                0 => [
                    'title' => [
                        'fr' => 'Commandes intégrées',
                        'en' => 'Built-in commands'
                    ]
                ],
                1 => [
                    'title' => [
                        'fr' => 'Registre compilé des commandes',
                        'en' => 'Compiled command registry'
                    ]
                ]
            ],
            'visibility' => 'public',
            'type' => 'developer-tools'
        ]
    ];
}
