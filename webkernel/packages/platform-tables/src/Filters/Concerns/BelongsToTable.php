<?php

namespace Webkernel\Platform\Tables\Filters\Concerns;

use Webkernel\Platform\Tables\Contracts\HasTable;
use Webkernel\Platform\Tables\Table;

trait BelongsToTable
{
    protected Table $table;

    public function table(Table $table): static
    {
        $this->table = $table;

        return $this;
    }

    public function getTable(): Table
    {
        return $this->table;
    }

    public function getLivewire(): HasTable
    {
        return $this->getTable()->getLivewire();
    }

    /**
     * @return array<string, mixed>
     */
    public function getState(): array
    {
        return $this->getLivewire()->getTableFilterState($this->getName()) ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getFormState(): array
    {
        return $this->getLivewire()->getTableFilterFormState($this->getName()) ?? [];
    }
}
