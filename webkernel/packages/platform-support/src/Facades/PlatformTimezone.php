<?php

namespace Webkernel\Platform\Support\Facades;

use Closure;
use Webkernel\Platform\Support\TimezoneManager;
use Webkernel\Container\Facade;

/**
 * @method static void set(string | Closure | null $timezone)
 * @method static string get()
 *
 * @see TimezoneManager
 */
class PlatformTimezone extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return TimezoneManager::class;
    }
}
