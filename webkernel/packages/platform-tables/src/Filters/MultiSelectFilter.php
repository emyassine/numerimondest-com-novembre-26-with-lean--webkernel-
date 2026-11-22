<?php

namespace Webkernel\Platform\Tables\Filters;

/**
 * @deprecated Use `SelectFilter` with the `multiple()` method instead.
 */
class MultiSelectFilter extends SelectFilter
{
    public function isMultiple(): bool
    {
        return true;
    }
}
