<?php

namespace Webkernel\Platform\Pages\Concerns;

trait HasTopbar
{
    protected bool $hasTopbar = true;

    public function hasTopbar(): bool
    {
        return $this->hasTopbar;
    }
}
