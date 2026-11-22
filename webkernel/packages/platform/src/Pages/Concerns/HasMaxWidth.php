<?php

namespace Webkernel\Platform\Pages\Concerns;

use Webkernel\Platform\Support\Enums\Width;

trait HasMaxWidth
{
    protected Width | string | null $maxWidth = null;

    public function getMaxWidth(): Width | string | null
    {
        return $this->maxWidth;
    }
}
