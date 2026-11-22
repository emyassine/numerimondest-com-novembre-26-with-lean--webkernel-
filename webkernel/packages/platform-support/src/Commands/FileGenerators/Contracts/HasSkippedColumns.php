<?php

namespace Webkernel\Platform\Support\Commands\FileGenerators\Contracts;

interface HasSkippedColumns
{
    /**
     * @return array<string, string>
     */
    public function getSkippedColumns(): array;
}
