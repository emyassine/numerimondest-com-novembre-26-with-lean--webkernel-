<?php

namespace Webkernel\Platform\Support\Contracts;

interface Collapsible
{
    public function isCollapsible(): bool;

    public function isCollapsed(): bool;
}
