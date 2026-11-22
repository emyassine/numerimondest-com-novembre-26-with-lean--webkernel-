<?php

namespace Webkernel\Liveview\Exceptions;

class ComponentNotFoundException extends \Exception
{
    use BypassViewHandler;
}
