<?php

namespace Webkernel\Platform\Support\Facades;

use Webkernel\Platform\Support\CliManager;
use Webkernel\Container\Facade;

/**
 * @method static array<string, array{path: string, viewNamespace: ?string}> getComponentLocations()
 * @method static array<string, array{path: string, viewNamespace: ?string}> getLivewireComponentLocations()
 * @method static void registerComponentLocation(string $directory, string $namespace, ?string $viewNamespace = null)
 * @method static void registerLivewireComponentLocation(string $directory, string $namespace, ?string $viewNamespace = null)
 *
 * @see CliManager
 */
class PlatformCli extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CliManager::class;
    }
}
