<?php

namespace Webkernel\ORM\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class CollectedBy
{
    /**
     * Create a new attribute instance.
     *
     * @param  class-string<\Webkernel\ORM\Collection<*, *>>  $collectionClass
     */
    public function __construct(public string $collectionClass)
    {
    }
}
