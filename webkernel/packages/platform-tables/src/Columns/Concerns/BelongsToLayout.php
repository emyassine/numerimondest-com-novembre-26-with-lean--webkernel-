<?php

namespace Webkernel\Platform\Tables\Columns\Concerns;

use Webkernel\Platform\Tables\Columns\Layout\Component;

trait BelongsToLayout
{
    protected ?Component $layout = null;

    public function layout(?Component $layout): static
    {
        $this->layout = $layout;

        return $this;
    }

    public function getLayout(): ?Component
    {
        return $this->layout;
    }
}
