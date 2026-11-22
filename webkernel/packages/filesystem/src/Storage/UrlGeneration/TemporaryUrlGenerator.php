<?php
declare(strict_types=1);

namespace Webkernel\Filesystem\Storage\UrlGeneration;

use DateTimeInterface;
use Webkernel\Filesystem\Storage\Config;
use Webkernel\Filesystem\Storage\UnableToGenerateTemporaryUrl;

interface TemporaryUrlGenerator
{
    /**
     * @throws UnableToGenerateTemporaryUrl
     */
    public function temporaryUrl(string $path, DateTimeInterface $expiresAt, Config $config): string;
}
