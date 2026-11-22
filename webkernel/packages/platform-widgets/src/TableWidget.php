<?php

namespace Webkernel\Platform\Widgets;

use Webkernel\Platform\Actions\Concerns\InteractsWithActions;
use Webkernel\Platform\Actions\Contracts\HasActions;
use Webkernel\Platform\Schemas\Concerns\InteractsWithSchemas;
use Webkernel\Platform\Schemas\Contracts\HasSchemas;
use Webkernel\Platform\Tables\Concerns\InteractsWithTable;
use Webkernel\Platform\Tables\Contracts\HasTable;
use Webkernel\Platform\Tables\Enums\PaginationMode;
use Webkernel\Platform\Tables\Table;
use Webkernel\View\Htmlable;

class TableWidget extends Widget implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable {
        makeTable as makeBaseTable;
    }

    /**
     * @var view-string
     */
    protected string $view = 'webkernel-widgets::table-widget';

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    protected static ?string $heading = null;

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    protected function getTableHeading(): string | Htmlable | null
    {
        return static::$heading;
    }

    protected function makeTable(): Table
    {
        return $this->makeBaseTable()
            ->heading(
                $this->getTableHeading() ?? (string) str(class_basename(static::class))
                    ->beforeLast('Widget')
                    ->kebab()
                    ->replace('-', ' ')
                    ->ucwords(),
            )
            ->paginationMode(PaginationMode::Simple);
    }
}
