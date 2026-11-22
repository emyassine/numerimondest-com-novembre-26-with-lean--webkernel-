<?php

namespace Webkernel\Liveview\Features\SupportWireables;

use Webkernel\Liveview\Wireable;
use Webkernel\Liveview\Mechanisms\HandleComponents\Synthesizers\ArrayShapedSynth;
use Webkernel\Liveview\Mechanisms\HandleComponents\Synthesizers\Synth;

class WireableSynth extends Synth implements ArrayShapedSynth
{
    public static $key = 'wrbl';

    static function match($target)
    {
        return is_object($target) && $target instanceof Wireable;
    }

    static function unwrapForValidation($target)
    {
        return $target->toLivewire();
    }

    function dehydrate($target, $dehydrateChild)
    {
        $data = $target->toLivewire();

        foreach ($data as $key => $child) {
            $data[$key] = $dehydrateChild($key, $child);
        }

        return [
            $data,
            ['class' => get_class($target)],
        ];
    }

    function hydrate($value, $meta, $hydrateChild) {
        // Verify class implements Wireable even though checksum protects this...
        if (! isset($meta['class']) || ! is_a($meta['class'], Wireable::class, true)) {
            throw new \Exception('Livewire: Invalid wireable class.');
        }

        foreach ($value as $key => $child) {
            $value[$key] = $hydrateChild($key, $child);
        }

        return $meta['class']::fromLivewire($value);
    }

    function set(&$target, $key, $value) {
        $target->{$key} = $value;
    }
}
