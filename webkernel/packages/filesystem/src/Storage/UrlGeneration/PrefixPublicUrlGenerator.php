<?php

declare(strict_types=1);

namespace Webkernel\Filesystem\Storage\UrlGeneration;

use Webkernel\Filesystem\Storage\Config;
use Webkernel\Filesystem\Storage\PathPrefixer;

class PrefixPublicUrlGenerator implements PublicUrlGenerator
{
    private PathPrefixer $prefixer;

    public function __construct(string $urlPrefix)
    {
        $this->prefixer = new PathPrefixer($urlPrefix, '/');
    }

    public function publicUrl(string $path, Config $config): string
    {
        return $this->prefixer->prefixPath($path);
    }
}
