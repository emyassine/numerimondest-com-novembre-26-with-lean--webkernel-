<?php

namespace Webkernel\Liveview\Features\SupportIsolating;

use Webkernel\Liveview\ComponentHook;

class SupportIsolating extends ComponentHook
{
    public function dehydrate($context)
    {
        if ($this->shouldIsolate()) {
            $context->addMemo('isolate', true);
        }
    }

    public function shouldIsolate()
    {
        return $this->component->getAttributes()
            ->filter(fn ($i) => is_subclass_of($i, BaseIsolate::class))
            ->count() > 0;
    }
}
