<?php

namespace Webkernel\Liveview\Attributes;

use Attribute;
use Webkernel\Liveview\Features\SupportEvents\BaseOn;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class On extends BaseOn
{
    //
}
