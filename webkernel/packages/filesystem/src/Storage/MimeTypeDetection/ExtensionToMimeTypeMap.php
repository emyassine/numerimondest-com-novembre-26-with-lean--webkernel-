<?php

declare(strict_types=1);

namespace Webkernel\Filesystem\Storage\MimeTypeDetection;

interface ExtensionToMimeTypeMap
{
    public function lookupMimeType(string $extension): ?string;
}
