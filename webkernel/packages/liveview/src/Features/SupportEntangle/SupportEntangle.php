<?php

namespace Webkernel\Liveview\Features\SupportEntangle;

use Webkernel\Liveview\ComponentHook;
use Webkernel\Liveview\Webkernel\ViewDirectives;
use Webkernel\View\View;

class SupportEntangle extends ComponentHook
{
    public static function provide()
    {
        View::directive('entangle', [ViewDirectives::class, 'entangle']);
    }
}
