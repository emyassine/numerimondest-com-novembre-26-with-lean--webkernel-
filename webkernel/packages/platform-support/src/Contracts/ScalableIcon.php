<?php

namespace Webkernel\Platform\Support\Contracts;

use Webkernel\Platform\Support\Enums\IconSize;

interface ScalableIcon
{
    public function getIconForSize(IconSize $size): string;
}
