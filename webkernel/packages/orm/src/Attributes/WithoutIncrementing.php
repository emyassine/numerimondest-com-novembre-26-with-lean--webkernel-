<?php

namespace Webkernel\ORM\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class WithoutIncrementing
{
    /**
     * Create a new attribute instance.
     */
    public function __construct()
    {
        //
    }
}
