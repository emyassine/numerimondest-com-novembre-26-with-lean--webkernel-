<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Platform\Providers;

use Webkernel\ServiceProvider\PackageMetadata;
use Webkernel\Platform\Compiler\CompiledPanelArtifactBuilder;

final class PlatformPackageDeclaration extends PackageMetadata
{
    protected const array METADATA = [
        'prefix' => 'webkernel-platform',
        'artifacts' => [CompiledPanelArtifactBuilder::class],
        'catalog' => [
            'title' => ['en' => 'Platform', 'fr' => 'Platform'],
            'summary' => [
                'en' => 'Webkernel Platform panels, forms, tables, and schemas.',
                'fr' => 'Panneaux, formulaires, tableaux et schémas de Webkernel Platform.',
            ],
            'category' => ['en' => 'Frontend', 'fr' => 'Frontend'],
            'features' => [],
            'visibility' => 'public',
            'type' => 'frontend',
        ],
    ];
}
