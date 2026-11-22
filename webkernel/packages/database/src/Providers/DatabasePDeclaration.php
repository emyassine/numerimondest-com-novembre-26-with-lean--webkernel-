<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Database\Providers;

use Webkernel\ServiceProvider\PackageMetadata;

final class DatabasePDeclaration extends PackageMetadata
{
    protected const array METADATA = [
        'prefix' => 'database',
        'catalog' => [
            'title' => [
                'fr' => 'Base de données',
                'en' => 'Database'
            ],
            'summary' => [
                'fr' => 'Connexions PDO configurées pour Webkernel.',
                'en' => 'Configured PDO connections for Webkernel applications.'
            ],
            'category' => [
                'fr' => 'Fondations',
                'en' => 'Foundation'
            ],
            'features' => [
                0 => [
                    'title' => [
                        'fr' => 'Connexions nommées',
                        'en' => 'Named connections'
                    ]
                ]
            ],
            'visibility' => 'public',
            'type' => 'foundation'
        ]
    ];
}
