<?php

use Webkernel\Platform\Actions\Exports\Http\Controllers\DownloadExport;
use Webkernel\Platform\Actions\Imports\Http\Controllers\DownloadImportFailureCsv;
use Webkernel\Route\Route;

$prefix = config('platform.system_route_prefix', 'platform');

Route::middleware(['signed', 'platform.actions'])
    ->name('platform.')
    ->prefix($prefix)
    ->group(function (): void {
        Route::get('/exports/{export}/download', DownloadExport::class)
            ->name('exports.download');

        Route::get('/imports/{import}/failed-rows/download', DownloadImportFailureCsv::class)
            ->name('imports.failed-rows.download');
    });
