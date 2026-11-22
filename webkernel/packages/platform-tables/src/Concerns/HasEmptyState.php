<?php

namespace Webkernel\Platform\Tables\Concerns;

use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Actions\ActionGroup;
use Webkernel\View\Contracts\View;
use Webkernel\View\ViewResult;

/**
 * @deprecated Override the `table()` method to configure the table.
 */
trait HasEmptyState
{
    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    protected function getTableEmptyState(): View | ViewResult | null
    {
        return null;
    }

    /**
     * @deprecated Override the `table()` method to configure the table.
     *
     * @return array<Action | ActionGroup>
     */
    protected function getTableEmptyStateActions(): array
    {
        return [];
    }

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    protected function getTableEmptyStateDescription(): ?string
    {
        return null;
    }

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    protected function getTableEmptyStateHeading(): ?string
    {
        return null;
    }

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    protected function getTableEmptyStateIcon(): ?string
    {
        return null;
    }
}
