<?php

namespace Webkernel\Platform\Tables\Columns\Concerns;

use Webkernel\Platform\Tables\Contracts\HasTable;
use Webkernel\Platform\Tables\Table;

trait BelongsToTable
{
    protected ?Table $table = null;

    public function table(?Table $table): static
    {
        $this->table = $table;

        return $this;
    }

    public function getTable(): Table
    {
        return $this->table;
    }

    public function hasTable(): bool
    {
        return isset($this->table);
    }

    public function getLivewire(): HasTable
    {
        return $this->getTable()->getLivewire();
    }
}
