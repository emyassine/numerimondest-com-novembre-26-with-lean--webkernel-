<?php

namespace Webkernel\Liveview\Attributes;

use Attribute;
use Webkernel\Liveview\Features\SupportSession\BaseSession;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Session extends BaseSession
{
    //
}
