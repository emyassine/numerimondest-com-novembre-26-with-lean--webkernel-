<?php

declare(strict_types=1);

namespace Webkernel\Filesystem\Storage;

use InvalidArgumentException as BaseInvalidArgumentException;

class InvalidStreamProvided extends BaseInvalidArgumentException implements FilesystemException
{
}
