<?php

namespace Webkernel\Platform\Support\Concerns;

use Closure;
use Webkernel\View\Htmlable;

trait HasTooltip
{
    protected string | Htmlable | Closure | null $tooltip = null;

    public function tooltip(string | Htmlable | Closure | null $tooltip): static
    {
        $this->tooltip = $tooltip;

        return $this;
    }

    public function getTooltip(): string | Htmlable | null
    {
        return $this->evaluate($this->tooltip);
    }

    public function hasTooltip(): bool
    {
        return $this->tooltip !== null;
    }
}
