<?php

namespace Webkernel\Platform\Models\Contracts;

use Webkernel\View\Htmlable;

interface HasName
{
    public function getPlatformName(): string | Htmlable;
}
