<?php

namespace Webkernel\Platform\Support\View\Components;

use Webkernel\Platform\Support\View\Components\Contracts\HasColor;
use Webkernel\Platform\Support\View\Components\Contracts\HasDefaultGrayColor;

class CalloutComponent implements HasColor, HasDefaultGrayColor
{
    /**
     * @param  array<int, string>  $color
     * @return array<string, int>
     */
    public function getColorMap(array $color): array
    {
        return [];
    }
}
