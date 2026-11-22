<?php

namespace Webkernel\Liveview\Attributes;

use Attribute;
use Webkernel\Liveview\Features\SupportValidation\BaseValidate;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_ALL)]
class Validate extends BaseValidate
{
    //
}

