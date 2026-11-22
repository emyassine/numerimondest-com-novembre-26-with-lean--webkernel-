<?php

declare(strict_types=1);

namespace Webkernel\Filesystem\Storage;

class UnableToCheckFileExistence extends UnableToCheckExistence
{
    public function operation(): string
    {
        return FilesystemOperationFailed::OPERATION_FILE_EXISTS;
    }
}
