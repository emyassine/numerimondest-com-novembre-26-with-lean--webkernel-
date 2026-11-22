<?php

namespace Webkernel\Liveview\Features\SupportJsEvaluation;

use Webkernel\Liveview\Features\SupportAttributes\Attribute as LivewireAttribute;

#[\Attribute]
class BaseJs extends LivewireAttribute
{
    function dehydrate($context)
    {
        $name = $this->getName();

        $stringifiedMethod = $this->component->$name();

        $context->pushEffect('js', $stringifiedMethod, $name);
    }
}




