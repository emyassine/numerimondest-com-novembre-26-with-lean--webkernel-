<?php

namespace Webkernel\Platform\Support\Concerns;

use Closure;
use Webkernel\View\Htmlable;

trait HasBadgeTooltip
{
    protected string | Htmlable | Closure | null $badgeTooltip = null;

    public function badgeTooltip(string | Htmlable | Closure | null $tooltip): static
    {
        $this->badgeTooltip = $tooltip;

        return $this;
    }

    public function getBadgeTooltip(?string $badge = null): string | Htmlable | null
    {
        return $this->evaluate($this->badgeTooltip, [
            'badge' => $badge,
        ]);
    }
}
