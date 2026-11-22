<?php

namespace Webkernel\Platform\Infolists\View\Components\IconEntryComponent;

use Webkernel\Platform\Support\Colors\Color;
use Webkernel\Platform\Support\Facades\PlatformColor;
use Webkernel\Platform\Support\View\Components\ColorMaps\ComponentColorMap;
use Webkernel\Platform\Support\View\Components\Contracts\HasColor;
use Webkernel\Platform\Support\View\Components\Contracts\HasDefaultGrayColor;

class IconComponent implements HasColor, HasDefaultGrayColor
{
    /**
     * Since the icons in the entry are the only content, they should have a color that contrasts
     * at least 3:1 with the surface to remain compliant with WCAG AA standards.
     *
     * @ref https://www.w3.org/WAI/WCAG21/Understanding/non-text-contrast.html
     *
     * @param  array<int, string>  $color
     * @return array<string, int>
     */
    public function getColorMap(array $color): array
    {
        $gray = PlatformColor::getColor('gray');

        return ComponentColorMap::make($color)
            ->slot('text', surface: 'oklch(1 0 0)', minRatio: Color::WCAG_AA_NON_TEXT, fallback: 900)
            ->slot('dark:text', surface: $gray[800], minRatio: Color::WCAG_AA_NON_TEXT, maxShade: 600, shouldStartFromDarkest: true, fallback: 200)
            ->get();
    }
}
