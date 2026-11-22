<?php

declare(strict_types=1);

namespace Webkernel\Filesystem\Storage;

class UnableToCheckDirectoryExistence extends UnableToCheckExistence
{
    public function operation(): string
    {
        return FilesystemOperationFailed::OPERATION_DIRECTORY_EXISTS;
    }
}
