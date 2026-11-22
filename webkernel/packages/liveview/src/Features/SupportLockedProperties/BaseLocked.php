<?php

namespace Webkernel\Liveview\Features\SupportLockedProperties;

use Webkernel\Liveview\Features\SupportAttributes\Attribute as LivewireAttribute;

#[\Attribute]
class BaseLocked extends LivewireAttribute
{
    public function update()
    {
        throw new CannotUpdateLockedPropertyException($this->getName());
    }
}
