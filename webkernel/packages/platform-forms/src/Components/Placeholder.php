<?php

namespace Webkernel\Platform\Forms\Components;

use Webkernel\Platform\Infolists\Components\TextEntry;

/**
 * @deprecated Use `TextEntry` with the `state()` method instead.
 */
class Placeholder extends TextEntry
{
    public function content(mixed $content): static
    {
        $this->state($content);

        return $this;
    }

    public function getContent(): mixed
    {
        return $this->getState();
    }
}
