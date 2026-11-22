<?php

namespace Webkernel\Liveview\Features\SupportLifecycleHooks;

use Webkernel\Liveview\Exceptions\BypassViewHandler;

class DirectlyCallingLifecycleHooksNotAllowedException extends \Exception
{
    use BypassViewHandler;

    public function __construct($method, $component)
    {
        parent::__construct(
            "Unable to call lifecycle method [{$method}] directly on component: [{$component}]"
        );
    }
}
