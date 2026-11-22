<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Liveview\Providers;

use Webkernel\ServiceProvider\PackageMetadata;

final class LiveviewPackageDeclaration extends PackageMetadata
{
    protected const array METADATA = [
        'prefix' => 'liveview',
        'catalog' => [
            'title' => ['en' => 'Liveview', 'fr' => 'Liveview'],
            'summary' => [
                'en' => 'Reactive server-rendered components integrated with Webkernel.',
                'fr' => 'Composants réactifs rendus côté serveur et intégrés à Webkernel.',
            ],
            'category' => ['en' => 'Frontend', 'fr' => 'Frontend'],
            'features' => [],
            'visibility' => 'public',
            'type' => 'frontend',
        ],
    ];
}
