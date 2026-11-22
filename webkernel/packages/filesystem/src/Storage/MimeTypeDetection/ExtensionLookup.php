<?php
declare(strict_types=1);

namespace Webkernel\Filesystem\Storage\MimeTypeDetection;

interface ExtensionLookup
{
    public function lookupExtension(string $mimetype): ?string;

    /**
     * @return string[]
     */
    public function lookupAllExtensions(string $mimetype): array;
}
