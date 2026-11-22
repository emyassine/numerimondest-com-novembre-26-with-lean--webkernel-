<?php

namespace Webkernel\Platform\Actions\Exports\Enums;

use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Actions\Exports\Downloaders\Contracts\Downloader;
use Webkernel\Platform\Actions\Exports\Downloaders\CsvDownloader;
use Webkernel\Platform\Actions\Exports\Downloaders\XlsxDownloader;
use Webkernel\Platform\Actions\Exports\Enums\Contracts\ExportFormat as ExportFormatInterface;
use Webkernel\Platform\Actions\Exports\Models\Export;
use \Webkernel\Route\URL;

enum ExportFormat: string implements ExportFormatInterface
{
    case Csv = 'csv';

    case Xlsx = 'xlsx';

    public function getDownloader(): Downloader
    {
        return match ($this) {
            self::Csv => app(CsvDownloader::class),
            self::Xlsx => app(XlsxDownloader::class),
        };
    }

    public function getDownloadNotificationAction(Export $export, string $authGuard): Action
    {
        return Action::make("download_{$this->value}")
            ->label(__("webkernel-actions::export.notifications.completed.actions.download_{$this->value}.label"))
            ->url(rtrim(request()->getBaseUrl(), '/') . URL::signedRoute('platform.exports.download', ['authGuard' => $authGuard, 'export' => $export, 'format' => $this], absolute: false), shouldOpenInNewTab: true)
            ->markAsRead();
    }
}
