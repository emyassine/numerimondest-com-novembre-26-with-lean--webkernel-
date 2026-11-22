<?php

namespace Webkernel\Platform\Notifications\Concerns;

use Webkernel\Platform\Support\Concerns\HasIconColor as BaseTrait;

trait HasIconColor
{
    use BaseTrait {
        getIconColor as getBaseIconColor;
    }

    /**
     * @return string | array<string> | null
     */
    public function getIconColor(): string | array | null
    {
        return $this->getBaseIconColor() ?? $this->getStatus();
    }
}
