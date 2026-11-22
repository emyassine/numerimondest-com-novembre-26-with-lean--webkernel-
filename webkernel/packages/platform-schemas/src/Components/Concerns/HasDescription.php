<?php

namespace Webkernel\Platform\Schemas\Components\Concerns;

use Closure;
use Webkernel\View\Htmlable;

trait HasDescription
{
    protected string | Htmlable | Closure | null $description = null;

    public function description(string | Htmlable | Closure | null $description = null): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): string | Htmlable | null
    {
        return $this->evaluate($this->description);
    }
}
