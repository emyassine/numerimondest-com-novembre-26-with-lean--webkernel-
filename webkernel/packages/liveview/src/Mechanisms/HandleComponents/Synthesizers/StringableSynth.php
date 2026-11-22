<?php

namespace Webkernel\Liveview\Mechanisms\HandleComponents\Synthesizers;

use Webkernel\Utilities\Stringable;

class StringableSynth extends Synth {
    public static $key = 'str';

    static function match($target) {
        return $target instanceof Stringable;
    }

    function dehydrate($target) {
        return [$target->__toString(), []];
    }

    function hydrate($value) {
        return str($value);
    }
}
