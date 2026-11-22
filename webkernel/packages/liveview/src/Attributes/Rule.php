<?php

namespace Webkernel\Liveview\Attributes;

use Attribute;
use Webkernel\Liveview\Features\SupportValidation\BaseRule;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_ALL)]
class Rule extends BaseRule
{
    //
}

