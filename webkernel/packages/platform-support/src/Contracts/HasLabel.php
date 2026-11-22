<?php

namespace Webkernel\Platform\Support\Contracts;

use Webkernel\View\Htmlable;

interface HasLabel
{
    public function getLabel(): string | Htmlable | null;
}
