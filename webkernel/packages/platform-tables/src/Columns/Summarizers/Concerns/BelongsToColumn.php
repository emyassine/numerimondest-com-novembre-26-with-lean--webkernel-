<?php

namespace Webkernel\Platform\Tables\Columns\Summarizers\Concerns;

use Webkernel\Platform\Tables\Columns\Column;
use Webkernel\Platform\Tables\Contracts\HasTable;
use Webkernel\Platform\Tables\Table;

trait BelongsToColumn
{
    protected Column $column;

    public function column(Column $column): static
    {
        $this->column = $column;

        return $this;
    }

    public function getColumn(): Column
    {
        return $this->column;
    }

    public function getTable(): Table
    {
        return $this->getColumn()->getTable();
    }

    public function getLivewire(): HasTable
    {
        return $this->getTable()->getLivewire();
    }
}
