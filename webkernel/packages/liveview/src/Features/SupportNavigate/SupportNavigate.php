<?php

namespace Webkernel\Liveview\Features\SupportNavigate;

use Webkernel\Liveview\ComponentHook;
use Webkernel\Liveview\Webkernel\ViewDirectives;
use Webkernel\View\View;

class SupportNavigate extends ComponentHook
{
    static function provide()
    {
        View::directive('persist', [ViewDirectives::class, 'persist']);
        View::directive('endpersist', [ViewDirectives::class, 'endpersist']);

        app('liveview')->useScriptTagAttributes([
            'data-navigate-once' => true,
        ]);

    }
}
