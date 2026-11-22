<?php

namespace Webkernel\Liveview\Mechanisms\HandleSynths;

class CorruptPersistedValueException extends \Exception
{
    public function __construct($previous = null)
    {
        parent::__construct('Livewire encountered corrupt persisted property data.', previous: $previous);
    }
}
