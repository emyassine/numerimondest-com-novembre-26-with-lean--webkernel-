<?php

namespace Webkernel\Platform\Actions\Events;

use Webkernel\Platform\Actions\Action;
use \Webkernel\Events\Dispatchable;

class ActionCalled
{
    use Dispatchable;

    public function __construct(
        protected Action $action,
    ) {}

    public function getAction(): Action
    {
        return $this->action;
    }
}
