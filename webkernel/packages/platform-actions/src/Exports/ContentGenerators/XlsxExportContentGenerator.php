<?php

namespace Webkernel\Platform\Actions\Exports\ContentGenerators;

use Webkernel\Platform\Actions\Exports\Models\Export;
use Webkernel\Exporters\Csv\Reader as CsvReader;
use Webkernel\Exporters\Csv\Statement;
use Webkernel\Exporters\Xlsx\Common\Entity\Row;
use Webkernel\Exporters\Xlsx\Writer\XLSX\Writer;

class XlsxExportContentGenerator
{
    public function __invoke(Export $export, Writer $writer): void
    {
        $disk = $export->getFileDisk();
        $directory = $export->getFileDirectory();
        $csvDelimiter = $export->exporter::getCsvDelimiter();

        $writeRowsFromFile = static function (string $file) use ($csvDelimiter, $disk, $writer): void {
            $csvReader = CsvReader::from($disk->readStream($file));
            $csvReader->setDelimiter($csvDelimiter);
            $csvResults = (new Statement)->process($csvReader);

            foreach ($csvResults->getRecords() as $row) {
                $writer->addRow(Row::fromValues($row));
            }
        };

        $writeRowsFromFile($directory . DIRECTORY_SEPARATOR . 'headers.csv');

        foreach ($disk->files($directory) as $file) {
            if (str($file)->endsWith('headers.csv')) {
                continue;
            }

            if (! str($file)->endsWith('.csv')) {
                continue;
            }

            $writeRowsFromFile($file);
        }
    }
}
