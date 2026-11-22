<?php

namespace Webkernel\ORM\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class UseFactory
{
    /**
     * Create a new attribute instance.
     *
     * @param  class-string<\Webkernel\ORM\Factories\Factory>  $factoryClass
     */
    public function __construct(public string $factoryClass)
    {
    }
}
