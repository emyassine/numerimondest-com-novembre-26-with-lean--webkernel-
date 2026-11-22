<?php

namespace Webkernel\Liveview;

interface Wireable
{
    public function toLivewire();

    public static function fromLivewire($value);
}
