<?php

namespace Webkernel\Liveview\Features\SupportJsEvaluation;

use function Webkernel\Liveview\store;

trait HandlesJsEvaluation
{
    function js($expression, ...$params)
    {
        store($this)->push('js', compact('expression', 'params'));
    }
}
