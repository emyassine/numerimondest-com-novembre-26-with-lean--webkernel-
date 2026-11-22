<?php

namespace Webkernel\Platform\Support\View\Components;

use Webkernel\Platform\Support\Facades\PlatformColor;
use Webkernel\Platform\Support\View\Components\ColorMaps\ComponentColorMap;
use Webkernel\Platform\Support\View\Components\Contracts\HasColor;
use Webkernel\Platform\Support\View\Components\Contracts\HasDefaultGrayColor;

class BadgeComponent implements HasColor, HasDefaultGrayColor
{
    /**
     * @param  array<int, string>  $color
     * @return array<string, int>
     */
    public function getColorMap(array $color): array
    {
        $gray = PlatformColor::getColor('gray');

        return ComponentColorMap::make($color)
            ->slot('text', surface: $color[50], fallback: 900)
            ->slot('dark:text', surface: $gray[600], maxShade: 500, shouldStartFromDarkest: true, fallback: 200)
            ->get();
    }
}
