<?php

namespace Webkernel\Platform\Schemas\Concerns;

use Webkernel\Platform\Schemas\Contracts\HasSchemas;
use Webkernel\Liveview\Component;

trait BelongsToLivewire
{
    /**
     * @var (Component & HasSchemas) | null
     */
    protected ?HasSchemas $livewire = null;

    /**
     * @param  (Component & HasSchemas) | null  $livewire
     */
    public function livewire(?HasSchemas $livewire = null): static
    {
        $this->livewire = $livewire;

        return $this;
    }

    public function getLivewire(): Component & HasSchemas
    {
        return $this->livewire;
    }
}
