<?php

namespace Webkernel\Platform\Support\Facades;

use BackedEnum;
use Webkernel\Platform\Support\Icons\IconManager;
use Webkernel\View\Htmlable;
use Webkernel\Container\Facade;

/**
 * @method static string | null resolve(string $name)
 *
 * @see IconManager
 */
class PlatformIcon extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return IconManager::class;
    }

    /**
     * @param  array<string, string | BackedEnum | Htmlable>  $icons
     */
    public static function register(array $icons): void
    {
        static::resolved(function (IconManager $iconManager) use ($icons): void {
            $iconManager->register($icons);
        });
    }
}
