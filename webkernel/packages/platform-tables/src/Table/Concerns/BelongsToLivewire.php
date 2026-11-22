<?php

namespace Webkernel\Platform\Tables\Table\Concerns;

use Webkernel\Platform\Tables\Contracts\HasTable;

trait BelongsToLivewire
{
    protected HasTable $livewire;

    public function livewire(HasTable $livewire): static
    {
        $this->livewire = $livewire;

        return $this;
    }

    public function getLivewire(): HasTable
    {
        return $this->livewire;
    }
}
