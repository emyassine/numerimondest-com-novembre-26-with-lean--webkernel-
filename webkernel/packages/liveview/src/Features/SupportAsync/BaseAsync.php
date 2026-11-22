<?php

namespace Webkernel\Liveview\Features\SupportAsync;

use Webkernel\Liveview\Features\SupportAttributes\Attribute as LivewireAttribute;

#[\Attribute]
class BaseAsync extends LivewireAttribute
{
    public function dehydrate($context)
    {
        $methodName = $this->getName();

        $context->pushMemo('async', $methodName);
    }
}
