<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\ServiceProvider\Providers;

use Webkernel\ServiceProvider\PackageMetadata;

final class ServiceProviderPDeclaration extends PackageMetadata
{
    protected const array METADATA = [
        'prefix' => 'service_provider',
        'catalog' => [
            'title' => [
                'fr' => 'Fournisseurs de services',
                'en' => 'Service Providers'
            ],
            'summary' => [
                'fr' => 'Contrat déclaratif partagé des fournisseurs de services Webkernel.',
                'en' => 'Shared declarative service provider contract for Webkernel packages.'
            ],
            'category' => [
                'fr' => 'Fondations',
                'en' => 'Foundation'
            ],
            'features' => [
                0 => [
                    'title' => [
                        'fr' => 'Contrats déclaratifs de packages',
                        'en' => 'Declarative package contracts'
                    ]
                ],
                1 => [
                    'title' => [
                        'fr' => 'Déclarations à la compilation',
                        'en' => 'Build-time declarations'
                    ]
                ]
            ],
            'visibility' => 'public',
            'type' => 'foundation'
        ]
    ];
}
