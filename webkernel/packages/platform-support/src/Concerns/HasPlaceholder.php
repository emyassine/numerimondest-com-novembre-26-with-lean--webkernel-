<?php

namespace Webkernel\Platform\Support\Concerns;

use Closure;
use Webkernel\View\Htmlable;

trait HasPlaceholder
{
    protected string | Htmlable | Closure | null $placeholder = null;

    public function placeholder(string | Htmlable | Closure | null $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function getPlaceholder(): string | Htmlable | null
    {
        return $this->evaluate($this->placeholder);
    }
}
