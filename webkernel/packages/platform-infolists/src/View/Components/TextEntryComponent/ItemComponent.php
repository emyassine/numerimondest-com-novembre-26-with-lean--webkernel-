<?php

namespace Webkernel\Platform\Infolists\View\Components\TextEntryComponent;

use Webkernel\Platform\Support\Facades\PlatformColor;
use Webkernel\Platform\Support\View\Components\ColorMaps\ComponentColorMap;
use Webkernel\Platform\Support\View\Components\Contracts\HasColor;

class ItemComponent implements HasColor
{
    /**
     * @param  array<int, string>  $color
     * @return array<string, int>
     */
    public function getColorMap(array $color): array
    {
        $gray = PlatformColor::getColor('gray');

        return ComponentColorMap::make($color)
            ->slot('text', surface: 'oklch(1 0 0)', fallback: 900)
            ->slot('dark:text', surface: $gray[800], maxShade: 600, shouldStartFromDarkest: true, fallback: 200)
            ->get();
    }
}
