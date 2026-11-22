<?php

namespace Webkernel\Platform\Support\Components;

use Webkernel\Platform\Support\Concerns\Configurable;
use Webkernel\Platform\Support\Concerns\EvaluatesClosures;
use Webkernel\Platform\Support\Concerns\Macroable;
use Webkernel\Utilities\Traits\Conditionable;
use Webkernel\Utilities\Traits\Tappable;

abstract class Component
{
    use Conditionable;
    use Configurable;
    use EvaluatesClosures;
    use Macroable;
    use Tappable;
}
