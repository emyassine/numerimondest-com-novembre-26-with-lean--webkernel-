<?php

namespace Webkernel\Liveview\Features\SupportJsEvaluation;

use Webkernel\Liveview\ComponentHook;

class SupportJsEvaluation extends ComponentHook
{
    function dehydrate($context)
    {
        if (! $this->storeHas('js')) return;

        $context->addEffect('xjs', $this->storeGet('js'));
    }
}
