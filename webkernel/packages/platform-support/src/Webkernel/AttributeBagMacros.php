<?php declare(strict_types=1);
//> This file is part of Platform, integrated with Webkernel.
//> Platform is licensed under the MIT license.

namespace Webkernel\Platform\Support\Webkernel;

use Webkernel\Platform\Support\View\ComponentAttributeBag;
use Webkernel\View\AttributeBag;

/** Registers Platform's attribute helpers on Webkernel's canonical attribute bag. */
final class AttributeBagMacros
{
    public static function register(): void
    {
        if (AttributeBag::hasMacro('color')) {
            return;
        }

        AttributeBag::macro('color', function (string|\Webkernel\Platform\Support\View\Components\Contracts\HasColor $component, string|array|null $color): ComponentAttributeBag {
            return (new ComponentAttributeBag($this->getAttributes()))->color($component, $color);
        });

        AttributeBag::macro('grid', function (array|int|null $columns = [], \Webkernel\Platform\Support\Enums\GridDirection $direction = \Webkernel\Platform\Support\Enums\GridDirection::Row): ComponentAttributeBag {
            return (new ComponentAttributeBag($this->getAttributes()))->grid($columns, $direction);
        });

        AttributeBag::macro('gridColumn', function (array|int|string|null $span = [], array|int|string|null $start = [], array|int|string|null $order = [], bool $isHidden = false): ComponentAttributeBag {
            return (new ComponentAttributeBag($this->getAttributes()))->gridColumn($span, $start, $order, $isHidden);
        });
    }
}
