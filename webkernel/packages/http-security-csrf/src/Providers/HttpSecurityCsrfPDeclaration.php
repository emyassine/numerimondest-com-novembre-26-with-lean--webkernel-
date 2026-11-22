<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\HttpSecurity\Csrf\Providers;

use Webkernel\ServiceProvider\PackageMetadata;

final class HttpSecurityCsrfPDeclaration extends PackageMetadata
{
    protected const array METADATA = [
        'prefix' => 'http_security_csrf',
        'catalog' => [
            'title' => [
                'fr' => 'Protection CSRF',
                'en' => 'CSRF Protection'
            ],
            'summary' => [
                'fr' => 'Génération et vérification de jetons CSRF liés à la session.',
                'en' => 'Session-backed CSRF token generation and verification.'
            ],
            'category' => [
                'fr' => 'Sécurité',
                'en' => 'Security'
            ],
            'features' => [
                0 => [
                    'title' => [
                        'fr' => 'Génération de jetons CSRF',
                        'en' => 'CSRF token generation'
                    ]
                ],
                1 => [
                    'title' => [
                        'fr' => 'Validation des requêtes',
                        'en' => 'Request validation'
                    ]
                ]
            ],
            'visibility' => 'public',
            'type' => 'http'
        ]
    ];
}
