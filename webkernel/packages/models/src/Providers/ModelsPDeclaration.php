<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Models\Providers;

use Webkernel\ServiceProvider\PackageMetadata;

final class ModelsPDeclaration extends PackageMetadata
{
    protected const array METADATA = [
        'prefix' => 'models',
        'catalog' => [
            'title' => [
                'fr' => 'Modèles',
                'en' => 'Models'
            ],
            'summary' => [
                'fr' => 'Modèles explicites reposant sur PDO pour les données d\'application.',
                'en' => 'Explicit PDO-backed models for application data.'
            ],
            'category' => [
                'fr' => 'Fondations',
                'en' => 'Foundation'
            ],
            'features' => [
                0 => [
                    'title' => [
                        'fr' => 'Persistance typée et préparée',
                        'en' => 'Typed prepared persistence'
                    ]
                ],
                1 => [
                    'title' => [
                        'fr' => 'Suppression logique configurable',
                        'en' => 'Configurable soft deletion'
                    ]
                ]
            ],
            'visibility' => 'public',
            'type' => 'foundation'
        ]
    ];
}
