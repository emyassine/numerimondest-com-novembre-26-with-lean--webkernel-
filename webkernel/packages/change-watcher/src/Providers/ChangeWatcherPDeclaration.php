<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\ChangeWatcher\Providers;

use Webkernel\ServiceProvider\PackageMetadata;

final class ChangeWatcherPDeclaration extends PackageMetadata
{
    protected const array METADATA = [
        'prefix' => 'change_watcher',
        'catalog' => [
            'title' => [
                'fr' => 'Surveillance des changements',
                'en' => 'Change Watcher'
            ],
            'summary' => [
                'fr' => 'Suivi portable des changements de fichiers et publication atomique des caches compilés.',
                'en' => 'Portable filesystem dependency tracking and atomic publication of compiled caches.'
            ],
            'category' => [
                'fr' => 'Surveillance et cache',
                'en' => 'Change tracking and cache'
            ],
            'features' => [
                0 => [
                    'title' => [
                        'fr' => 'Suivi des sources',
                        'en' => 'Source tracking'
                    ]
                ],
                1 => [
                    'title' => [
                        'fr' => 'Publication atomique du cache',
                        'en' => 'Atomic cache publication'
                    ]
                ]
            ],
            'visibility' => 'public',
            'type' => 'runtime'
        ]
    ];
}
