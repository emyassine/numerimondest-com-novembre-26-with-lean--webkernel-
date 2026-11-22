<?php

declare(strict_types=1);

namespace Webkernel\Filesystem\Storage;

interface PathNormalizer
{
    public function normalizePath(string $path): string;
}
