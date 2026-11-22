<?php

namespace Webkernel\Platform\Forms\Components\Contracts;

use Closure;

interface CanDisableOptions
{
    public function disableOptionWhen(bool | Closure $callback): static;
}
