<?php

namespace Webkernel\Liveview\Features\SupportLazyLoading;

use Webkernel\Liveview\Features\SupportAttributes\Attribute as LivewireAttribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
class BaseDefer extends LivewireAttribute
{
    public function __construct(
        public $isolate = null,
        public $bundle = null,
    ) {}
}
