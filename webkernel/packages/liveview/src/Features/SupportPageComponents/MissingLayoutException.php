<?php

namespace Webkernel\Liveview\Features\SupportPageComponents;

use Exception;

class MissingLayoutException extends Exception
{
    function __construct(string $layout)
    {
        parent::__construct('Livewire page component layout view not found: ['.$layout.']');
    }
}
