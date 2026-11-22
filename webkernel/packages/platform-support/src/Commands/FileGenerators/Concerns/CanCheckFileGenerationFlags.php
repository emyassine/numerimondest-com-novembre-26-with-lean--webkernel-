<?php

namespace Webkernel\Platform\Support\Commands\FileGenerators\Concerns;

trait CanCheckFileGenerationFlags
{
    protected function hasFileGenerationFlag(string $flag): bool
    {
        return in_array($flag, config('platform.file_generation.flags') ?? []);
    }
}
