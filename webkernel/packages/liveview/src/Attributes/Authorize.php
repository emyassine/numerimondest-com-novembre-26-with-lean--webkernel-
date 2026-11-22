<?php

namespace Webkernel\Liveview\Attributes;

use Attribute;
use Webkernel\Liveview\Features\SupportAuthorization\BaseAuthorize;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_METHOD)]
class Authorize extends BaseAuthorize
{
    //
}
