<?php

namespace Webkernel\Platform\Support\Contracts;

use Webkernel\View\Htmlable;

interface HasDescription
{
    public function getDescription(): string | Htmlable | null;
}
