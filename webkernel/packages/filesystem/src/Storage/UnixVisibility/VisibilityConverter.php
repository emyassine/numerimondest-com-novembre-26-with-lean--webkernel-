<?php

declare(strict_types=1);

namespace Webkernel\Filesystem\Storage\UnixVisibility;

interface VisibilityConverter
{
    public function forFile(string $visibility): int;
    public function forDirectory(string $visibility): int;
    public function inverseForFile(int $visibility): string;
    public function inverseForDirectory(int $visibility): string;
    public function defaultForDirectories(): int;
}
