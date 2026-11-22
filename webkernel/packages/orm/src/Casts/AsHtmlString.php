<?php

namespace Webkernel\ORM\Casts;

use Webkernel\ORM\Contracts\Castable;
use Webkernel\ORM\Contracts\CastsAttributes;
use Webkernel\ORM\Contracts\SerializesCastableAttributes;
use Webkernel\View\HtmlString;

class AsHtmlString implements Castable
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @param  array  $arguments
     * @return \Webkernel\ORM\Contracts\CastsAttributes<\Webkernel\View\HtmlString, string|HtmlString>
     */
    public static function castUsing(array $arguments)
    {
        return new class implements CastsAttributes, SerializesCastableAttributes
        {
            public function get($model, $key, $value, $attributes)
            {
                return isset($value) ? new HtmlString($value) : null;
            }

            public function set($model, $key, $value, $attributes)
            {
                return isset($value) ? (string) $value : null;
            }

            public function serialize($model, $key, $value, $attributes)
            {
                return isset($value) ? (string) $value : null;
            }
        };
    }
}
