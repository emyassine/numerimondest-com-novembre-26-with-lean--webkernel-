<?php

namespace Webkernel\Platform\Actions\Exports\Enums\Contracts;

use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Actions\Exports\Downloaders\Contracts\Downloader;
use Webkernel\Platform\Actions\Exports\Models\Export;

interface ExportFormat
{
    public function getDownloader(): Downloader;

    public function getDownloadNotificationAction(Export $export, string $authGuard): Action;
}
