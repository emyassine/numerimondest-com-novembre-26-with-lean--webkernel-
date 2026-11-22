<?php

namespace Webkernel\Liveview\Features\SupportPageComponents;

use Webkernel\Liveview\Features\SupportAttributes\Attribute as LivewireAttribute;

#[\Attribute]
class BaseLayout extends LivewireAttribute
{
    function __construct(
        public $name,
        public $params = [],
    ) {}
}
