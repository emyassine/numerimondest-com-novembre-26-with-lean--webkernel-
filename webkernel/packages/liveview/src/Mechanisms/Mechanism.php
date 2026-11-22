<?php

namespace Webkernel\Liveview\Mechanisms;

abstract class Mechanism
{
    function register()
    {
        \Webkernel\Container\Container::getInstance()->instance(static::class, $this);
    }

    function boot()
    {
        //
    }
}
