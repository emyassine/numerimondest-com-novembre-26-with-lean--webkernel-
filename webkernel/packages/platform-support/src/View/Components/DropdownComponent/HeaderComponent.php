<?php

namespace Webkernel\Platform\Support\View\Components\DropdownComponent;

use Webkernel\Platform\Support\Facades\PlatformColor;
use Webkernel\Platform\Support\View\Components\ColorMaps\ComponentColorMap;
use Webkernel\Platform\Support\View\Components\Contracts\HasColor;
use Webkernel\Platform\Support\View\Components\Contracts\HasDefaultGrayColor;

class HeaderComponent implements HasColor, HasDefaultGrayColor
{
    /**
     * @param  array<int, string>  $color
     * @return array<string, int>
     */
    public function getColorMap(array $color): array
    {
        $gray = PlatformColor::getColor('gray');

        return ComponentColorMap::make($color)
            ->slot('text', surface: 'oklch(1 0 0)', minShade: 600, fallback: 950)
            ->slot('dark:text', surface: $gray[900], maxShade: 400, shouldStartFromDarkest: true, fallback: 200)
            ->get();
    }
}
