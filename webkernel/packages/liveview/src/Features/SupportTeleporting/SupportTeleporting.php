<?php

namespace Webkernel\Liveview\Features\SupportTeleporting;

use Webkernel\Liveview\Webkernel\ViewDirectives;
use Webkernel\View\View;
use Webkernel\Liveview\ComponentHook;

class SupportTeleporting extends ComponentHook
{
    static function provide()
    {
        View::directive('teleport', [ViewDirectives::class, 'teleport']);
        View::directive('endteleport', [ViewDirectives::class, 'endteleport']);
    }
}
