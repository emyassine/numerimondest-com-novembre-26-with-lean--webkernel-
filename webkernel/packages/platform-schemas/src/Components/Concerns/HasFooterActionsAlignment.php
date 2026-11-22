<?php

namespace Webkernel\Platform\Schemas\Components\Concerns;

use Closure;
use Webkernel\Platform\Support\Enums\Alignment;

trait HasFooterActionsAlignment
{
    protected Alignment | Closure | null $footerActionsAlignment = Alignment::Start;

    public function footerActionsAlignment(Alignment | Closure | null $alignment): static
    {
        $this->footerActionsAlignment = $alignment;

        return $this;
    }

    public function getFooterActionsAlignment(): ?Alignment
    {
        return $this->evaluate($this->footerActionsAlignment);
    }
}
