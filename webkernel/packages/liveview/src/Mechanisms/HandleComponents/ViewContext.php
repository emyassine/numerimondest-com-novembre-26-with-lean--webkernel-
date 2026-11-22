<?php

namespace Webkernel\Liveview\Mechanisms\HandleComponents;

class ViewContext
{
    public function __construct(
        public array $slots = [],
        public array $pushes = [],
        public array $prepends = [],
        public array $sections = [],
    ) {}
}
