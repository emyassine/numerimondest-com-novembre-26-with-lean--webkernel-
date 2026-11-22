<?php

namespace Webkernel\Liveview\Features\SupportWireables;

use Webkernel\Liveview\ComponentHook;

class SupportWireables extends ComponentHook
{
    static function provide()
    {
        app('liveview')->propertySynthesizer(WireableSynth::class);
    }
}
