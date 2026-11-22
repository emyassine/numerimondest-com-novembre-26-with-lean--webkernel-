<?php

namespace Webkernel\Liveview;

class Wrapped
{
    protected $fallback;

    function __construct(public $target) {}

    function withFallback($fallback)
    {
        $this->fallback = $fallback;

        return $this;
    }

    function __call($method, $params)
    {
        if (! method_exists($this->target, $method)) {
            return $this->fallback instanceof \Closure ? ($this->fallback)() : $this->fallback;
        }

        try {
            return \Webkernel\Container\Container::getInstance()->call([$this->target, $method], $params);
        } catch (\Throwable $e) {
            $shouldPropagate = true;

            $stopPropagation = function () use (&$shouldPropagate) {
                $shouldPropagate = false;
            };

            trigger('exception', $this->target, $e, $stopPropagation);

            $shouldPropagate && throw $e;
        }
    }
}


