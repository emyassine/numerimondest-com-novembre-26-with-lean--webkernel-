<?php

namespace Webkernel\Platform\Support\Commands\Concerns;

use Webkernel\Platform\Commands\CacheComponentsCommand;

trait CanCachePanelComponents
{
    protected function canCachePanelComponents(): bool
    {
        return class_exists(CacheComponentsCommand::class);
    }
}
