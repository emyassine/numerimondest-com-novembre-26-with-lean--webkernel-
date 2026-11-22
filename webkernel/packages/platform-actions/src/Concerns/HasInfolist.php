<?php

namespace Webkernel\Platform\Actions\Concerns;

use Closure;
use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Actions\ActionGroup;
use Webkernel\Platform\Schemas\Components\Component;

trait HasInfolist
{
    /**
     * @deprecated Use `schema() instead.
     *
     * @param  array<Component | Action | ActionGroup> | Closure | null  $infolist
     */
    public function infolist(array | Closure | null $infolist): static
    {
        $this->schema($infolist);

        return $this;
    }
}
