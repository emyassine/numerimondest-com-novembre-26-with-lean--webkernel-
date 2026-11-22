<?php

declare(strict_types=1);

namespace Webkernel\Filesystem\Storage\UrlGeneration;

use Webkernel\Filesystem\Storage\Config;
use Webkernel\Filesystem\Storage\UnableToGeneratePublicUrl;

interface PublicUrlGenerator
{
    /**
     * @throws UnableToGeneratePublicUrl
     */
    public function publicUrl(string $path, Config $config): string;
}
