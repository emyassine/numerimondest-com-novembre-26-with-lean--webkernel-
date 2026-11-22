<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Session\Providers;

use Webkernel\ServiceProvider\PackageMetadata;

final class SessionPDeclaration extends PackageMetadata
{
    protected const array METADATA = [
        'prefix' => 'session',
        'catalog' => [
            'title' => [
                'fr' => 'Sessions',
                'en' => 'Sessions'
            ],
            'summary' => [
                'fr' => 'Gestion native et sécurisée des sessions serveur.',
                'en' => 'Secure native server-side session management.'
            ],
            'category' => [
                'fr' => 'Sécurité',
                'en' => 'Security'
            ],
            'features' => [
                0 => [
                    'title' => [
                        'fr' => 'Cycle de vie et données de session',
                        'en' => 'Session lifecycle and data'
                    ]
                ],
                1 => [
                    'title' => [
                        'fr' => 'Rotation et invalidation sécurisées',
                        'en' => 'Secure rotation and invalidation'
                    ]
                ]
            ],
            'visibility' => 'public',
            'type' => 'foundation'
        ]
    ];
}
