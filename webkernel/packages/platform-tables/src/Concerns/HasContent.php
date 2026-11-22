<?php

namespace Webkernel\Platform\Tables\Concerns;

use Webkernel\View\Contracts\View;
use Webkernel\View\ViewResult;

/**
 * @deprecated Override the `table()` method to configure the table.
 */
trait HasContent
{
    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    protected function getTableContent(): View | ViewResult | null
    {
        return null;
    }

    /**
     * @deprecated Override the `table()` method to configure the table.
     *
     * @return array<string, int | null> | null
     */
    protected function getTableContentGrid(): ?array
    {
        return null;
    }

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    protected function getTableContentFooter(): View | ViewResult | null
    {
        return null;
    }
}
