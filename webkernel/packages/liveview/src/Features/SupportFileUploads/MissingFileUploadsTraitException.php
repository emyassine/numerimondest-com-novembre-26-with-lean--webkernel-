<?php

namespace Webkernel\Liveview\Features\SupportFileUploads;

use Exception;
use Webkernel\Liveview\Exceptions\BypassViewHandler;

class MissingFileUploadsTraitException extends Exception
{
    use BypassViewHandler;

    public function __construct($component)
    {
        parent::__construct(
            "Cannot handle file upload without [Webkernel\Liveview\Features\SupportFileUploads\WithFileUploads] trait on the [{$component->getName()}] component class."
        );
    }
}
