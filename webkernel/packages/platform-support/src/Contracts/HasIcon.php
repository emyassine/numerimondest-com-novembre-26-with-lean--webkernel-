<?php

namespace Webkernel\Platform\Support\Contracts;

use BackedEnum;
use Webkernel\View\Htmlable;

interface HasIcon
{
    public function getIcon(): string | BackedEnum | Htmlable | null;
}
