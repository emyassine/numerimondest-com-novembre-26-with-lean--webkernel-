<?php

namespace Webkernel\Liveview\Features\SupportMagicActions;

use Webkernel\Liveview\ComponentHook;

use function Webkernel\Liveview\on;

class SupportMagicActions extends ComponentHook
{
    public static $magicActions = [
        '$refresh',
        '$set',
        '$sync',
        '$commit',
    ];

    public static function provide()
    {
        on('call', function ($component, $method, $params, $componentContext, $returnEarly, $context) {
            if (! in_array($method, self::$magicActions)) {
                return;
            }

            $returnEarly();
        });
    }
}