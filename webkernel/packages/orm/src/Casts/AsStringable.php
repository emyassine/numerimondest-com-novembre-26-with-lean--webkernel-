<?php

namespace Webkernel\ORM\Casts;

use Webkernel\ORM\Contracts\Castable;
use Webkernel\ORM\Contracts\CastsAttributes;
use Webkernel\Utilities\Stringable;

class AsStringable implements Castable
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @param  array  $arguments
     * @return \Webkernel\ORM\Contracts\CastsAttributes<\Webkernel\Utilities\Stringable, string|\Stringable>
     */
    public static function castUsing(array $arguments)
    {
        return new class implements CastsAttributes
        {
            public function get($model, $key, $value, $attributes)
            {
                return isset($value) ? new Stringable($value) : null;
            }

            public function set($model, $key, $value, $attributes)
            {
                return isset($value) ? (string) $value : null;
            }
        };
    }
}
