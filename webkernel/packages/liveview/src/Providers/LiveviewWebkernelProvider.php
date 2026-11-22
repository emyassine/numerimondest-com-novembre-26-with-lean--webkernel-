<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Liveview\Providers;

use Webkernel\Liveview\LivewireManager;
use Webkernel\Liveview\Mechanisms\DataStore;
use Webkernel\ServiceProvider;

final class LiveviewWebkernelProvider extends ServiceProvider
{
    public const array CONFIG = [
        'liveview' => __DIR__ . '/../../config/liveview.php',
    ];

    public const array BINDINGS = [
        //> Liveview signs component checksums with the Webkernel `encrypter`.
        'encrypter' => [
            'concrete' => \Webkernel\Cryptography\ConfiguredEncrypter::class,
            'lifetime' => 'scoped',
        ],
        DataStore::class => [
            'concrete' => DataStore::class,
            'lifetime' => 'scoped',
        ],
        LivewireManager::class => [
            'concrete' => LivewireManager::class,
            'lifetime' => 'singleton',
        ],
    ];

    public const array VIEW_DIRECTIVES = [
        'liveview' => [\Webkernel\Liveview\Webkernel\ViewDirectives::class, 'liveview'],
        'entangle' => [\Webkernel\Liveview\Webkernel\ViewDirectives::class, 'entangle'],
        'script' => [\Webkernel\Liveview\Webkernel\ViewDirectives::class, 'script'],
        'endscript' => [\Webkernel\Liveview\Webkernel\ViewDirectives::class, 'endscript'],
        'assets' => [\Webkernel\Liveview\Webkernel\ViewDirectives::class, 'assets'],
        'endassets' => [\Webkernel\Liveview\Webkernel\ViewDirectives::class, 'endassets'],
        'persist' => [\Webkernel\Liveview\Webkernel\ViewDirectives::class, 'persist'],
        'endpersist' => [\Webkernel\Liveview\Webkernel\ViewDirectives::class, 'endpersist'],
        'teleport' => [\Webkernel\Liveview\Webkernel\ViewDirectives::class, 'teleport'],
        'endteleport' => [\Webkernel\Liveview\Webkernel\ViewDirectives::class, 'endteleport'],
    ];

    public const array ROUTE_BOOTSTRAPPERS = [
        [\Webkernel\Liveview\Webkernel\Runtime::class, 'registerRoutes'],
    ];

    // Compiled deployments skip route files and route bootstrappers, so the
    // Liveview runtime must be booted explicitly for update and asset requests.
    public const array RUNTIME_BOOTSTRAPPERS = [
        [\Webkernel\Liveview\Webkernel\Runtime::class, 'boot'],
        [\Webkernel\Liveview\Features\SupportPageComponents\SupportPageComponents::class, 'registerLayoutViewMacros'],
    ];

    public const array PUBLIC_ASSETS = [
        __DIR__ . '/../../dist',
    ];

    public const array VIEWS = [
        'liveview' => [__DIR__ . '/../Features/SupportPagination/views'],
    ];
}
