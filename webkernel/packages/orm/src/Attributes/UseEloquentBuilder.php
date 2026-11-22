<?php

namespace Webkernel\ORM\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class UseEloquentBuilder
{
    /**
     * Create a new attribute instance.
     *
     * @param  class-string<\Webkernel\ORM\Builder>  $builderClass
     */
    public function __construct(public string $builderClass)
    {
    }
}
