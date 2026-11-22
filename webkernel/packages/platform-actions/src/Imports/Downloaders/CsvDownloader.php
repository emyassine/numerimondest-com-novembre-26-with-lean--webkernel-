<?php

namespace Webkernel\Platform\Actions\Imports\Downloaders;

use Webkernel\Platform\Actions\Imports\ContentGenerators\CsvImportFailureContentGenerator;
use Webkernel\Platform\Actions\Imports\Downloaders\Contracts\Downloader;
use Webkernel\Platform\Actions\Imports\Models\Import;
use Webkernel\Exporters\Csv\Writer;
use SplTempFileObject;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvDownloader implements Downloader
{
    public function __invoke(Import $import): StreamedResponse
    {
        $csv = Writer::createFromFileObject(new SplTempFileObject);

        app(CsvImportFailureContentGenerator::class)($import, $csv);

        return response()->streamDownload(function () use ($csv): void {
            foreach ($csv->chunk(1000) as $offset => $chunk) {
                echo $chunk;

                if ($offset % 1000) {
                    flush();
                }
            }
        }, __('webkernel-actions::import.failure_csv.file_name', [
            'import_id' => $import->getKey(),
            'csv_name' => (string) str($import->file_name)->beforeLast('.')->remove('.'),
        ]) . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
