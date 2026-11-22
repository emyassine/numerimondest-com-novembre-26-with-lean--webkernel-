<?php

namespace Webkernel\Filesystem\Contracts;

interface Factory
{
    /**
     * Get a filesystem implementation.
     *
     * @param  \UnitEnum|string|null  $name
     * @return \Webkernel\Filesystem\Contracts\Filesystem
     */
    public function disk($name = null);
}
