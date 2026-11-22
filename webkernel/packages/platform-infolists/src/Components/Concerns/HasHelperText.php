<?php

namespace Webkernel\Platform\Infolists\Components\Concerns;

use Closure;
use Webkernel\Platform\Schemas\Components\Component;
use Webkernel\Platform\Schemas\Components\Text;
use Webkernel\View\Htmlable;

trait HasHelperText
{
    public function helperText(string | Htmlable | Closure | null $text): static
    {
        $this->belowContent(function (Component $component) use ($text): ?Text {
            $content = $component->evaluate($text);

            if (blank($content)) {
                return null;
            }

            return Text::make($content);
        });

        return $this;
    }
}
