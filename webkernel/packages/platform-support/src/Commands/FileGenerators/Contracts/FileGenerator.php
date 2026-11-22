<?php

namespace Webkernel\Platform\Support\Commands\FileGenerators\Contracts;

interface FileGenerator
{
    public function generate(): string;
}
