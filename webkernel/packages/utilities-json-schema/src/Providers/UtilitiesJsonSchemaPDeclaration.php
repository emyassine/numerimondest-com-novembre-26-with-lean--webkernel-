<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Utilities\JsonSchema\Providers;

use Webkernel\ServiceProvider\PackageMetadata;

final class UtilitiesJsonSchemaPDeclaration extends PackageMetadata
{
    protected const array METADATA = [
        'prefix' => 'utilities_json_schema',
        'catalog' => [
            'title' => [
                'fr' => 'Utilitaires JSON Schema',
                'en' => 'JSON Schema Utilities'
            ],
            'summary' => [
                'fr' => 'Outils de validation JSON Schema pour Webkernel.',
                'en' => 'JSON Schema validation utilities for Webkernel.'
            ],
            'category' => [
                'fr' => 'Validation JSON Schema',
                'en' => 'JSON Schema validation'
            ],
            'features' => [
                0 => [
                    'title' => [
                        'fr' => 'Validation JSON Schema',
                        'en' => 'JSON Schema validation'
                    ]
                ],
                1 => [
                    'title' => [
                        'fr' => 'API de validation réutilisable',
                        'en' => 'Reusable validation API'
                    ]
                ]
            ],
            'visibility' => 'public',
            'type' => 'foundation'
        ]
    ];
}
