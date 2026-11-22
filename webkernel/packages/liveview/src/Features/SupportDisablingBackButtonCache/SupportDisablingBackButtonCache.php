<?php

namespace Webkernel\Liveview\Features\SupportDisablingBackButtonCache;

use Webkernel\Liveview\ComponentHook;

use function Webkernel\Liveview\on;

class SupportDisablingBackButtonCache extends ComponentHook
{
    public static $disableBackButtonCache = false;

    public static function provide()
    {
        on('flush-state', function () {
            static::$disableBackButtonCache = false;
        });

        $kernel = app()->make(\Webkernel\HttpFoundation\Contracts\Kernel::class);

        if ($kernel->hasMiddleware(DisableBackButtonCacheMiddleware::class)) {
            return;
        }

        $kernel->pushMiddleware(DisableBackButtonCacheMiddleware::class);
    }

    public function boot()
    {
        static::$disableBackButtonCache = true;
    }
}
