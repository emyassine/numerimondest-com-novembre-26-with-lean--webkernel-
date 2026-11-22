<?php

namespace Webkernel\Liveview\Features\SupportPageComponents;

use Webkernel\Liveview\Features\SupportAttributes\Attribute as LivewireAttribute;

#[\Attribute]
class BaseTitle extends LivewireAttribute
{
    function __construct(
        public $content,
    ) {}
}
