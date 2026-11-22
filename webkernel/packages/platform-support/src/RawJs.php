<?php

namespace Webkernel\Platform\Support;

use Webkernel\View\Js;

class RawJs extends Js
{
    public function __construct(string $js)
    {
        $this->js = $js;
    }

    public static function make(string $js): static
    {
        return app(static::class, ['js' => $js]);
    }
}
