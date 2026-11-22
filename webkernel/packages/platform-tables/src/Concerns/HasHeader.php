<?php

namespace Webkernel\Platform\Tables\Concerns;

use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Actions\ActionGroup;
use Webkernel\View\Htmlable;
use Webkernel\View\Contracts\View;
use Webkernel\View\ViewResult;

/**
 * @deprecated Override the `table()` method to configure the table.
 */
trait HasHeader
{
    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    protected function getTableDescription(): string | Htmlable | null
    {
        return null;
    }

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    protected function getTableHeader(): View | ViewResult | Htmlable | null
    {
        return null;
    }

    /**
     * @deprecated Override the `table()` method to configure the table.
     *
     * @return array<Action | ActionGroup>
     */
    protected function getTableHeaderActions(): array
    {
        return [];
    }

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    protected function getTableHeading(): string | Htmlable | null
    {
        return null;
    }
}
