<?php

namespace Webkernel\Liveview\Exceptions;

class RootTagMissingFromViewException extends \Exception
{
    use BypassViewHandler;

    public function __construct()
    {
        parent::__construct(
            'Livewire encountered a missing root tag when trying to render a ' .
            "component.\n Ensure the component template has one root HTML element."
        );
    }
}
